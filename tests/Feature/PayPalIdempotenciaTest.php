<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use App\Services\CitaService;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Endurecimiento de la idempotencia y la concurrencia del pago PayPal:
 * doble clic en checkout, callback duplicado sin efectos secundarios,
 * carrera de dos capturas con lockForUpdate y estado inconsistente
 * (completed pero cita sin confirmar) que NO confirma en silencio.
 */
class PayPalIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    private function makeCliente(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Cliente Idem',
            'email'    => 'idemcliente@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
    }

    private function makeAbogado(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Abogado Idem',
            'email'    => 'idemabog@test.com',
            'password' => 'password123',
            'rol'      => 'abogado',
            'activo'   => true,
        ]);
    }

    private function makeCitaPendiente(Usuario $cliente, string $modalidad = 'presencial'): Cita
    {
        return Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $this->makeAbogado()->id,
            'fecha'       => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => $modalidad,
            'descripcion' => 'Cita de prueba',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.0,
        ]);
    }

    /** Respuesta de capture PayPal válida (montos/referencias de la cita). */
    private function payloadCapturaValida(Cita $cita, array $overrides = []): array
    {
        $base = [
            'id'     => $cita->paypal_order_id,
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'custom_id'  => (string) $cita->id,
                'invoice_id' => $cita->codigo,
                'payments'   => [
                    'captures' => [[
                        'id'     => 'CAP-123',
                        'status' => 'COMPLETED',
                        'amount' => ['currency_code' => 'USD', 'value' => '35.00'],
                    ]],
                ],
            ]],
        ];
        return array_replace_recursive($base, $overrides);
    }

    /** Doble clic en "Pagar" → ambas llamadas derivan a la MISMA orden estable. */
    public function test_doble_clic_checkout_no_crea_estado_invalido(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);

        $this->mock(PayPalService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->twice()
                ->andReturn([
                    'order_id'    => 'ORD-1',
                    'approve_url' => 'https://www.paypal.com/checkout?token=ORD-1',
                ]);
        });

        $this->actingAs($cliente)->get(route('cliente.paypal.checkout', $cita->id))->assertStatus(302);
        $this->actingAs($cliente)->get(route('cliente.paypal.checkout', $cita->id))->assertStatus(302);

        $cita->refresh();
        $this->assertSame('ORD-1', $cita->paypal_order_id);
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
    }

    /** Callback ya procesado → el fast-path NO vuelve a confirmar. */
    public function test_callback_ya_procesado_no_vuelve_a_confirmar(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        // Primer callback: pago válido → se registra y se confirma.
        $this->mock(PayPalService::class, function ($mock) use ($cita) {
            $mock->shouldReceive('captureOrder')->andReturn($this->payloadCapturaValida($cita));
        });
        $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']))->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());

        // Segundo callback del mismo Order: confirmar NO se vuelve a invocar.
        $spy = $this->spy(CitaService::class);
        $res = $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));
        $res->assertStatus(302);
        $spy->shouldNotHaveReceived('confirmar');

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame('CAP-123', $cita->transaction_id);
    }

    /**
     * Carrera de dos capturas: mientras este request llama a PayPal, OTRO
     * request concurrente ya completó la fila. lockForUpdate + guard hacen
     * que NO se repitan efectos secundarios (no hay confirmar de más).
     */
    public function test_carrera_doble_capture_no_repite_efectos(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        // La respuesta es válida, pero el "fake" simula el commit del otro
        // request modificando la fila DURANTE la llamada a PayPal.
        $this->mock(PayPalService::class, function ($mock) use ($cita) {
            $mock->shouldReceive('captureOrder')->andReturnUsing(function () use ($cita) {
                $cita->update([
                    'payment_status' => 'completed',
                    'transaction_id' => 'CAP-CONCURRENTE',
                    'paid_at'        => now(),
                ]);
                return $this->payloadCapturaValida($cita, [
                    'purchase_units' => [[
                        'payments' => ['captures' => [['id' => 'CAP-CONCURRENTE']]],
                    ]],
                ]);
            });
        });

        $res = $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));
        $res->assertStatus(302);

        $cita->refresh();
        // El guard del lock detectó la fila ya completada → sin confirmar extra.
        $this->assertSame('CAP-CONCURRENTE', $cita->transaction_id);
        $this->assertFalse($cita->estaConfirmada());
        $this->assertSame(0, VideoRoom::count());
    }

    /**
     * Estado inconsistente (payment_status=completed pero cita al pendiente):
     * entrar al callback NO confirma en silencio; se loguea el error y se
     * deja la cita como está, señalando soporte.
     */
    public function test_inconsistente_completed_no_confirmada_no_confirma_silencioso(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update([
            'paypal_order_id' => 'ORD-1',
            'payment_status'  => 'completed',
            'transaction_id'  => 'CAP-123',
            'paid_at'         => now(),
        ]);

        $this->mock(PayPalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });
        $spy = $this->spy(CitaService::class);
        Log::spy();

        $res = $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));
        $res->assertStatus(302);
        $res->assertSessionHasErrors('pago');

        $cita->refresh();
        $this->assertFalse($cita->estaConfirmada());
        $this->assertTrue($cita->estaPendiente());

        $spy->shouldNotHaveReceived('confirmar');
        Log::shouldHaveReceived('error')
            ->with(\Mockery::on(fn ($msg) => str_starts_with($msg, '[PayPal] estado inconsistente')), \Mockery::any());
    }
}