<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayPalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** PayPalService se mockea; nunca se hace una llamada HTTP real. */

    private function makeCliente(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Cliente Pago',
            'email'    => 'pagocliente@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
    }

    private function makeAbogado(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Abogado Pago',
            'email'    => 'pagoabog@test.com',
            'password' => 'password123',
            'rol'      => 'abogado',
            'activo'   => true,
        ]);
    }

    private function makeCitaPendiente(Usuario $cliente, string $modalidad = 'presencial', float $monto = 35.0): Cita
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
            'monto'       => $monto,
        ]);
    }

    /** Respuesta de capture PayPal válida por defecto para una cita. */
    private function responseCapturaValida(Cita $cita, array $overrides = []): array
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

    private function mockCaptura(array $respuesta): void
    {
        $this->mock(PayPalService::class, function ($mock) use ($respuesta) {
            $mock->shouldReceive('captureOrder')->andReturn($respuesta);
        });
    }

    private function mockCapturaError(): void
    {
        $this->mock(PayPalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->andThrow(new \RuntimeException('PayPal captura falló'));
        });
    }

    /** A. Cliente inicia pago → la cita sigue pendiente_pago y guarda el order id. */
    public function test_inicia_pago_y_queda_pendiente(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);

        $this->mock(PayPalService::class, function ($mock) {
            $mock->shouldReceive('createOrder')->andReturn([
                'order_id'    => 'ORD-1',
                'approve_url' => 'https://www.paypal.com/checkout/approve',
            ]);
        });

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.checkout', $cita->id));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
        $this->assertSame('ORD-1', $cita->paypal_order_id);
    }

    /** B. Cancelación PayPal → la cita sigue pendiente_pago y NO se confirma. */
    public function test_cancelacion_mantiene_pendiente(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.cancel', ['token' => 'ORD-1']));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
        $this->assertSame('cancelled', $cita->payment_status);
    }

    /** C. Order ID inexistente en BD → no confirma (404). */
    public function test_order_id_inexistente_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $this->makeCitaPendiente($cliente); // cita sin ese order id

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-NO-EXISTE']));

        $response->assertStatus(404);

        $this->assertSame(0, Cita::where('estado', 'confirmada')->count());
    }

    /** D. Capture devuelve error → no confirma. */
    public function test_capture_con_error_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);
        $this->mockCapturaError();

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
    }

    /** E. Capture status distinto a COMPLETED → no confirma. */
    public function test_capture_status_no_completed_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita, [
            'purchase_units' => [[
                'payments' => ['captures' => [['status' => 'PENDING']]],
            ]],
        ]));

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);
        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
    }

    /** F. Monto incorrecto → no confirma. */
    public function test_monto_incorrecto_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita, [
            'purchase_units' => [[
                'payments' => ['captures' => [['amount' => ['currency_code' => 'USD', 'value' => '40.00']]]],
            ]],
        ]));

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);
        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
    }

    /** G. Moneda distinta de USD → no confirma. */
    public function test_moneda_no_usd_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita, [
            'purchase_units' => [[
                'payments' => ['captures' => [['amount' => ['currency_code' => 'EUR', 'value' => '35.00']]]],
            ]],
        ]));

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);
        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
    }

    /** H. Referencia de otra cita (custom_id distinto) → no confirma. */
    public function test_referencia_otra_cita_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        // El order id coincide con BD, pero custom_id/invoice apuntan a "otra cita".
        $this->mockCaptura($this->responseCapturaValida($cita, [
            'purchase_units' => [[
                'custom_id'  => '999',
                'invoice_id' => 'LEX-OTRA',
            ]],
        ]));

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);
        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
    }

    /** I. Capture válido → pago registrado y cita confirmada. */
    public function test_capture_valido_confirma_y_registra_pago(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita));

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame('completed', $cita->payment_status);
        $this->assertSame('CAP-123', $cita->transaction_id);
        $this->assertNotNull($cita->paid_at);
    }

    /** J. Callback duplicado del mismo Order → no duplica procesamiento. */
    public function test_callback_duplicado_no_duplica(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita));

        $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        // Segundo callback del mismo Order.
        $this->mockCaptura($this->responseCapturaValida($cita));
        $res2 = $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $res2->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame('completed', $cita->payment_status);
        $this->assertSame('CAP-123', $cita->transaction_id);
    }

    /** K. Cita virtual válida → confirmada y exactamente una VideoRoom. */
    public function test_cita_virtual_confirma_y_crea_una_video_room(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente, 'virtual');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita));

        $response = $this->actingAs($cliente)
            ->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** L. Callback repetido de cita virtual → sigue existiendo exactamente una VideoRoom. */
    public function test_callback_repetido_virtual_no_duplica_video_room(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente, 'virtual');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCaptura($this->responseCapturaValida($cita));
        $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        // Segundo callback.
        $this->mockCaptura($this->responseCapturaValida($cita));
        $this->actingAs($cliente)->get(route('cliente.paypal.return', ['token' => 'ORD-1']));

        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }
}
