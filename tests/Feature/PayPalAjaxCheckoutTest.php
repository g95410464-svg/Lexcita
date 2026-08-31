<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use App\Services\CitaService;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoints AJAX del PayPal JS SDK inline (create + capture):
 * creación de orden real, captura server-to-server + validación estricta,
 * persistencia bajo lock, idempotencia y garantía de que la cita solo se
 * confirma tras capture + pagoValido, nunca desde JavaScript.
 *
 * Rutas bajo prueba: cliente.paypal.create, cliente.paypal.capture.
 * El JS SDK NO confirma — todo pasa por Laravel server-to-server.
 */
class PayPalAjaxCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeCliente(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Cliente Ajax',
            'email'    => uniqid('ajax') . '@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
    }

    private function makeAbogado(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Abogado Ajax',
            'email'    => uniqid('abajax') . '@test.com',
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
            'descripcion' => 'Cita AJAX',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.0,
        ]);
    }

    /** Respuesta de capture PayPal válida (montos/referencias de la cita). */
    private function responseCapturaValida(Cita $cita): array
    {
        return [
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
    }

    private function mockCreateOrder(string $orderId = 'ORD-1'): void
    {
        $this->mock(PayPalService::class, function ($mock) use ($orderId) {
            $mock->shouldReceive('createOrder')->once()->andReturn([
                'order_id'    => $orderId,
                'approve_url' => 'https://www.paypal.com/checkout?token=' . $orderId,
            ]);
        });
    }

    private function mockCapture(array $respuesta): void
    {
        $this->mock(PayPalService::class, function ($mock) use ($respuesta) {
            $mock->shouldReceive('captureOrder')->once()->andReturn($respuesta);
        });
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST A — Crear orden para cita propia pendiente → 200 + orderID
    // ────────────────────────────────────────────────────────────────────────
    public function test_create_ajax_cita_propia_devuelve_order_id(): void
    {
        $cliente = $this->makeCliente();
        $cita    = $this->makeCitaPendiente($cliente);

        $this->mockCreateOrder('ORD-A');

        $res = $this->actingAs($cliente)
            ->postJson(route('cliente.paypal.create', $cita->id));

        $res->assertStatus(200)
            ->assertJson(['success' => true, 'orderID' => 'ORD-A']);

        $cita->refresh();
        $this->assertSame('ORD-A', $cita->paypal_order_id);
        $this->assertTrue($cita->estaPendiente());
        $this->assertSame('pending', $cita->payment_status);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST B — Crear orden para cita ajena → 404
    // ────────────────────────────────────────────────────────────────────────
    public function test_create_ajax_cita_ajena_devuelve_404(): void
    {
        $dueno = $this->makeCliente();
        $cita  = $this->makeCitaPendiente($dueno);

        $otro = Usuario::create([
            'nombre'   => 'Otro Cliente',
            'email'    => uniqid('otro') . '@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);

        $this->actingAs($otro)
            ->postJson(route('cliente.paypal.create', $cita->id))
            ->assertStatus(404);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST C — Captura válida (COMPLETED) → 200 + completed + confirmada
    // ────────────────────────────────────────────────────────────────────────
    public function test_capture_ajax_valido_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita    = $this->makeCitaPendiente($cliente, 'presencial');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCapture($this->responseCapturaValida($cita));

        $res = $this->actingAs($cliente)
            ->postJson(route('cliente.paypal.capture', $cita->id), ['orderID' => 'ORD-1']);

        $res->assertStatus(200)
            ->assertJson(['success' => true]);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame('completed', $cita->payment_status);
        $this->assertSame('CAP-123', $cita->transaction_id);
        $this->assertNotNull($cita->paid_at);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST D — orderID distinto al de la cita → 422 + no confirma
    // ────────────────────────────────────────────────────────────────────────
    public function test_capture_ajax_order_id_distinto_rechazado(): void
    {
        $cliente = $this->makeCliente();
        $cita    = $this->makeCitaPendiente($cliente, 'presencial');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $res = $this->actingAs($cliente)
            ->postJson(route('cliente.paypal.capture', $cita->id), ['orderID' => 'ORD-X']);

        $res->assertStatus(422);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertNull($cita->transaction_id);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST E — Captura sin estado COMPLETED → 422 + no confirma
    // ────────────────────────────────────────────────────────────────────────
    public function test_capture_ajax_no_completed_no_confirma(): void
    {
        $cliente = $this->makeCliente();
        $cita    = $this->makeCitaPendiente($cliente, 'presencial');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $this->mockCapture([
            'id'     => 'ORD-1',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'custom_id'  => (string) $cita->id,
                'invoice_id' => $cita->codigo,
                'payments'   => [
                    'captures' => [[
                        'id'     => 'CAP-PENDING',
                        'status' => 'PENDING',
                        'amount' => ['currency_code' => 'USD', 'value' => '35.00'],
                    ]],
                ],
            ]],
        ]);

        $res = $this->actingAs($cliente)
            ->postJson(route('cliente.paypal.capture', $cita->id), ['orderID' => 'ORD-1']);

        $res->assertStatus(422);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertNull($cita->transaction_id);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST F — Doble captura idempotente (virtual) → 1 confirm, 1 VideoRoom
    // ────────────────────────────────────────────────────────────────────────
    public function test_capture_doble_ajax_idempotente_una_videoroom(): void
    {
        $cliente = $this->makeCliente();
        $cita    = $this->makeCitaPendiente($cliente, 'virtual');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        // Primera captura: flujo completo → confirmar llamado una vez.
        $this->mockCapture($this->responseCapturaValida($cita));

        $res1 = $this->actingAs($cliente)
            ->postJson(route('cliente.paypal.capture', $cita->id), ['orderID' => 'ORD-1']);
        $res1->assertStatus(200);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());

        // Segunda captura: fast-path idempotente → no re-confirma.
        $res2 = $this->actingAs($cliente)
            ->postJson(route('cliente.paypal.capture', $cita->id), ['orderID' => 'ORD-1']);
        $res2->assertStatus(200)
            ->assertJson(['success' => true]);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }

    // ────────────────────────────────────────────────────────────────────────
    // TEST G — El flujo cancel del frontend NO tiene endpoint que confirme
    // ────────────────────────────────────────────────────────────────────────
    public function test_cancel_no_confirma_y_mantiene_pendiente(): void
    {
        $cliente = $this->makeCliente();
        $cita    = $this->makeCitaPendiente($cliente, 'presencial');
        $cita->update(['paypal_order_id' => 'ORD-1']);

        $res = $this->actingAs($cliente)
            ->get(route('cliente.paypal.cancel', ['token' => 'ORD-1']));

        $res->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
        $this->assertNull($cita->transaction_id);
    }
}
