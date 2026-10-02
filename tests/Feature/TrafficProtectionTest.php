<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\ClientAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrafficProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['traffic.railway_ingress' => false, 'traffic.origin_secret' => null]);
    }

    private function usuario(string $email): Usuario
    {
        return Usuario::create([
            'nombre' => 'Prueba de limites', 'email' => $email,
            'password' => 'PasswordPrueba123', 'rol' => 'cliente', 'activo' => true,
        ]);
    }

    private function login(string $email = 'cliente@example.test')
    {
        return $this->postJson('/login', ['email' => $email, 'password' => 'Incorrecta123']);
    }

    public function test_login_limit_normalizes_email_and_recovers_after_the_window(): void
    {
        $this->freezeTime();
        for ($i = 0; $i < 5; $i++) {
            $this->login()->assertRedirect();
        }
        $blocked = $this->login('CLIENTE@example.test')->assertStatus(429)
            ->assertHeader('Retry-After', '60')->assertJsonPath('retry_after', 60);
        $this->assertStringContainsString('no-store', $blocked->headers->get('Cache-Control'));
        $this->travel(61)->seconds();
        $this->login()->assertRedirect();
    }

    public function test_another_account_on_the_same_office_ip_has_a_separate_budget(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login()->assertRedirect();
        }
        $this->login()->assertStatus(429);
        $this->login('otra@example.test')->assertRedirect();
    }

    public function test_rotating_email_does_not_bypass_the_ip_limit(): void
    {
        config(['traffic.limits.login_ip' => 3]);
        for ($i = 0; $i < 3; $i++) {
            $this->login("cliente{$i}@example.test")->assertRedirect();
        }
        $this->login('nuevo@example.test')->assertStatus(429);
    }

    public function test_array_email_is_validation_error_instead_of_server_error(): void
    {
        $this->postJson('/login', ['email' => ['bad'], 'password' => 'Password123'])
            ->assertUnprocessable();
    }

    public function test_railway_clients_are_separate_and_forwarded_headers_do_not_change_identity(): void
    {
        config(['traffic.railway_ingress' => true, 'traffic.limits.login_identity' => 1]);
        $this->withHeaders(['X-Real-IP' => '192.0.2.1'])->login()->assertRedirect();
        $this->withHeaders([
            'X-Real-IP' => '192.0.2.1', 'CF-Connecting-IP' => '192.0.2.99',
            'X-Forwarded-For' => '198.51.100.99',
        ])->login()->assertStatus(429);
        $this->withHeaders(['X-Real-IP' => '192.0.2.2'])->login()->assertRedirect();
    }

    public function test_direct_connections_ignore_untrusted_proxy_headers(): void
    {
        config(['traffic.limits.login_identity' => 1]);
        $this->withHeaders(['X-Real-IP' => '192.0.2.1'])->login()->assertRedirect();
        $this->withHeaders(['X-Real-IP' => '192.0.2.2', 'CF-Connecting-IP' => '192.0.2.3'])
            ->login()->assertStatus(429);
    }

    public function test_equivalent_ipv6_addresses_share_one_counter(): void
    {
        config(['traffic.railway_ingress' => true]);
        $a = Request::create('/');
        $b = Request::create('/');
        $a->headers->set('X-Real-IP', '2001:db8::1');
        $b->headers->set('X-Real-IP', '2001:0db8:0000:0000:0000:0000:0000:0001');
        $this->assertSame(ClientAddress::key($a), ClientAddress::key($b));
    }

    public function test_invalid_railway_header_falls_back_to_the_connection_address(): void
    {
        config(['traffic.railway_ingress' => true]);
        $request = Request::create('/', server: ['REMOTE_ADDR' => '192.0.2.1']);
        $request->headers->set('X-Real-IP', '192.0.2.2, 192.0.2.3');
        $this->assertSame(bin2hex(inet_pton('192.0.2.1')), ClientAddress::key($request));
    }

    public function test_registration_has_minute_and_hour_budgets_and_spanish_html(): void
    {
        $this->freezeTime();
        config(['traffic.limits.register_minute' => 2, 'traffic.limits.register_hour' => 3]);
        $this->postJson('/registro', [])->assertUnprocessable();
        $this->postJson('/registro', [])->assertUnprocessable();
        $this->post('/registro', [])->assertStatus(429)->assertSee('Demasiadas solicitudes.');
        $this->travel(61)->seconds();
        $this->postJson('/registro', [])->assertUnprocessable();
        $this->postJson('/registro', [])->assertStatus(429);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_slots_are_limited_before_queries_and_separated_by_user(): void
    {
        config(['traffic.limits.slots' => 2]);
        $first = $this->usuario('first@example.test');
        $second = $this->usuario('second@example.test');
        $this->actingAs($first);
        $this->getJson('/api/slots')->assertUnprocessable();
        $this->getJson('/api/slots')->assertUnprocessable();
        $this->getJson('/api/slots')->assertStatus(429);
        $this->actingAs($second)->getJson('/api/slots')->assertUnprocessable();
    }

    public function test_booking_is_limited_without_consuming_slots_or_logout(): void
    {
        config(['traffic.limits.booking' => 1]);
        $this->actingAs($this->usuario('booking@example.test'));
        $this->postJson('/cliente/nueva-cita', [])->assertUnprocessable();
        $this->postJson('/cliente/nueva-cita', [])->assertStatus(429);
        $this->getJson('/api/slots')->assertUnprocessable();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_video_and_broadcast_do_not_consume_the_portal_budget(): void
    {
        config(['traffic.limits.video' => 1, 'traffic.limits.broadcast' => 1]);
        $this->actingAs($this->usuario('video@example.test'));
        $this->postJson('/videollamada/inexistente/ice', [])->assertNotFound();
        $this->postJson('/videollamada/otro-token/ice', [])->assertStatus(429);
        Broadcast::shouldReceive('auth')->once()
            ->andThrow(new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException());
        $this->postJson('/broadcasting/auth', ['channel_name' => 'private-inexistente'])->assertForbidden();
        $this->postJson('/broadcasting/auth', ['channel_name' => 'private-inexistente'])->assertStatus(429);
        $this->get('/cliente/dashboard')->assertOk();
    }

    public function test_private_pages_cannot_be_cached_and_logout_is_always_available(): void
    {
        config(['traffic.limits.portal' => 1]);
        $this->actingAs($this->usuario('cache@example.test'));
        $response = $this->get('/cliente/dashboard')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get('/cliente/dashboard')->assertStatus(429);
        $this->post('/logout')->assertRedirect('/login');
        Auth::forgetGuards();
        $this->assertGuest();
        $response = $this->get('/registro')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_configured_origin_secret_blocks_bypass_but_preserves_health_checks(): void
    {
        config(['traffic.origin_secret' => 'test-only-origin-secret']);
        $this->get('/login')->assertForbidden();
        $this->withHeaders(['X-Lexcita-Origin' => 'incorrecto'])->get('/login')->assertForbidden();
        $this->get('/up')->assertOk();
        $this->withHeaders(['X-Lexcita-Origin' => 'test-only-origin-secret'])->get('/login')->assertOk();
    }

    public function test_sensitive_routes_have_the_expected_limiters(): void
    {
        $routes = Route::getRoutes();
        foreach ([
            'google.redirect' => 'oauth', 'google.callback' => 'oauth',
            'cliente.paypal.checkout' => 'payments', 'cliente.paypal.return' => 'payments',
            'cliente.paypal.create' => 'payments', 'cliente.paypal.capture' => 'payments',
            'pago.crear-sesion' => 'payments', 'interno.abogados.crear' => 'writes',
            'interno.citas.confirmar' => 'writes', 'cliente.cancelar' => 'writes',
        ] as $route => $limiter) {
            $this->assertContains('throttle:'.$limiter, $routes->getByName($route)->gatherMiddleware(), $route);
        }
    }
}
