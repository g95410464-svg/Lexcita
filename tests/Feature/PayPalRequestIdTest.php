<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifica el contrato de idempotencia PayPal-Request-Id a nivel de HTTP:
 * se usan CLAVES ESTABLES tanto para crear la orden como para capturarla,
 * de modo que un reintento de red / doble clic genere la MISMA operación
 * lógica ante PayPal (reutiliza el mismo Order) en lugar de duplicar.
 *
 * No hay mocks: el PayPalService real se ejecuta contra Http::fake().
 */
class PayPalRequestIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.paypal', [
            'client_id' => 'test-client',
            'secret'    => 'test-secret',
            'mode'      => 'sandbox',
            'currency'  => 'USD',
            'base_uri'  => 'https://api-m.sandbox.paypal.com',
            'timeout'   => 15,
        ]);
    }

    private function makeCliente(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Cliente RequestId',
            'email'    => 'reqidcliente@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
    }

    private function makeAbogado(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Abogado RequestId',
            'email'    => 'reqidabog@test.com',
            'password' => 'password123',
            'rol'      => 'abogado',
            'activo'   => true,
        ]);
    }

    private function makeCitaPendiente(Usuario $cliente): Cita
    {
        return Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $this->makeAbogado()->id,
            'fecha'       => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => 'presencial',
            'descripcion' => 'Cita de prueba',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.0,
        ]);
    }

    /** Endpoints de PayPal simulados a nivel HTTP (token, orders, capture). */
    private function fakerEsperado(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 't0k3n',
                'app_id'       => 'APP',
                'expires_in'   => 3600,
                'nonce'        => 'n',
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORD-1',
                'status' => 'APPROVED',
                'links'  => [
                    ['rel' => 'approve', 'href' => 'https://www.paypal.com/checkout?token=ORD-1'],
                ],
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/*' => Http::response([
                'id'            => 'ORD-1',
                'status'        => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [[
                        'captures' => [[
                            'id'     => 'CAP-1',
                            'status' => 'COMPLETED',
                            'amount' => ['currency_code' => 'USD', 'value' => '35.00'],
                        ]],
                    ]],
                ]],
            ], 200),
        ]);
    }

    private function peticionesCon(?\Closure $filtro): \Illuminate\Support\Collection
    {
        return collect(Http::recorded())
            ->map(fn (array $par) => $par[0])
            ->filter($filtro ?? fn () => true)
            ->values();
    }

    /** Reintento de createOrder → el MISMO PayPal-Request-Id en ambas llamadas. */
    public function test_create_order_envia_request_id_estable(): void
    {
        $this->fakerEsperado();
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);

        $servicio = new PayPalService();
        $servicio->createOrder($cita); // primer clic
        $servicio->createOrder($cita); // doble clic / reintento con retry del proxy

        $ordenes = $this->peticionesCon(
            fn ($req) => str_contains($req->url(), '/v2/checkout/orders')
        );

        $this->assertGreaterThanOrEqual(2, $ordenes->count());
        $ordenes->each(function ($req) use ($cita) {
            $this->assertContains('lexcita-create-' . $cita->id, $req->header('PayPal-Request-Id'));
        });

        // El token OAuth2 se pide una sola vez aunque createOrder se repita.
        $tokens = $this->peticionesCon(
            fn ($req) => str_contains($req->url(), '/v1/oauth2/token')
        );
        $this->assertSame(1, $tokens->count());
    }

    /** Reintento de capture tras timeout → el MISMO PayPal-Request-Id. */
    public function test_capture_reintenta_con_el_mismo_request_id(): void
    {
        $this->fakerEsperado();
        $servicio = new PayPalService();
        $servicio->captureOrder('ORD-1');
        $servicio->captureOrder('ORD-1'); // reintento de red

        $clave = 'lexcita-capture-' . md5('ORD-1');

        $capturas = $this->peticionesCon(
            fn ($req) => str_contains($req->url(), '/capture')
        );

        $this->assertSame(2, $capturas->count());
        $capturas->each(function ($req) use ($clave) {
            $this->assertContains($clave, $req->header('PayPal-Request-Id'));
        });
    }
}