<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Google\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['google.client_id' => 'test.apps.googleusercontent.com', 'google.client_secret' => 'test-secret',
            'google.redirect_uri' => 'http://localhost/auth/google/callback']);
    }

    private function begin(): array
    {
        $response = $this->get(route('google.redirect'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        return $query;
    }

    private function fakeIdentity(array|false $claims): void
    {
        $client = Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('fetchAccessTokenWithAuthCode')->once()->with('test-code')->andReturn(['id_token' => 'signed-by-google']);
        $client->shouldReceive('verifyIdToken')->once()->with('signed-by-google')->andReturn($claims);
        $this->app->instance(Client::class, $client);
    }

    private function claims(string $nonce, array $overrides = []): array
    {
        return array_replace([
            'sub' => 'google-account-123', 'aud' => config('google.client_id'), 'exp' => time() + 3600,
            'email' => 'cliente@gmail.com', 'email_verified' => true, 'name' => 'Cliente Google', 'nonce' => $nonce,
        ], $overrides);
    }

    public function test_redirect_uses_existing_client_exact_callback_state_and_nonce(): void
    {
        $query = $this->begin();
        $this->assertSame(config('google.client_id'), $query['client_id']);
        $this->assertSame(config('google.redirect_uri'), $query['redirect_uri']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertSame(session('google.oauth.state'), $query['state']);
        $this->assertSame(session('google.oauth.nonce'), $query['nonce']);
        $this->assertSame(64, strlen($query['state']));
        $this->assertNotSame($query['state'], $query['nonce']);
    }

    public function test_unconfigured_google_returns_to_password_login(): void
    {
        config(['google.client_secret' => null]);
        $this->get(route('google.redirect'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertNull(session('google.oauth'));
    }

    public static function invalidCallbacks(): array
    {
        return array_map(fn ($case) => [$case], ['no-session', 'missing-state', 'wrong-state', 'state-array',
            'code-array', 'missing-code', 'oversized-code', 'expired', 'cancelled', 'changed-callback']);
    }

    #[DataProvider('invalidCallbacks')]
    public function test_invalid_callback_never_contacts_google_or_creates_accounts(string $case): void
    {
        $query = $this->begin();
        $params = ['state' => $query['state'], 'code' => 'test-code'];
        match ($case) {
            'no-session' => session()->forget('google.oauth'),
            'missing-state' => $params['state'] = null,
            'wrong-state' => $params['state'] = str_repeat('x', 64),
            'state-array' => $params['state'] = ['bad'],
            'code-array' => $params['code'] = ['bad'],
            'missing-code' => $params['code'] = null,
            'oversized-code' => $params['code'] = str_repeat('x', 8193),
            'expired' => session()->put('google.oauth.expires_at', now()->subSecond()->timestamp),
            'cancelled' => $params['error'] = 'access_denied',
            'changed-callback' => config(['google.redirect_uri' => 'https://another.example/auth/google/callback']),
        };
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('fetchAccessTokenWithAuthCode');
        $this->app->instance(Client::class, $client);
        $this->get(route('google.callback', $params))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertGuest();
        $this->assertNull(session('google.oauth'));
    }

    public function test_verified_new_user_gets_client_dashboard_and_callback_is_single_use(): void
    {
        $query = $this->begin();
        $oldSession = session()->getId();
        $this->fakeIdentity($this->claims($query['nonce']));
        $callback = route('google.callback', ['state' => $query['state'], 'code' => 'test-code', 'rol' => 'admin']);
        $this->get($callback)->assertRedirect(route('cliente.dashboard'));
        $user = Usuario::sole();
        $this->assertSame('cliente', $user->rol);
        $this->assertTrue($user->activo);
        $this->assertNotSame($oldSession, session()->getId());
        Auth::forgetGuards();
        $this->get(route('cliente.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->get($callback)->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('usuarios', 1);
    }

    public static function roles(): array
    {
        return [['admin', true, 'interno.dashboard'], ['abogado', true, 'abogado.dashboard'], ['cliente', false, 'login']];
    }

    #[DataProvider('roles')]
    public function test_existing_account_keeps_role_and_inactive_account_is_rejected(string $role, bool $active, string $route): void
    {
        $user = Usuario::create(['nombre' => 'Existente', 'email' => 'cliente@gmail.com', 'password' => 'local-secret', 'rol' => $role, 'activo' => $active]);
        $query = $this->begin();
        $this->fakeIdentity($this->claims($query['nonce']));
        $this->get(route('google.callback', ['state' => $query['state'], 'code' => 'test-code']))->assertRedirect(route($route));
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertSame($role, $user->fresh()->rol);
        $active ? $this->assertAuthenticatedAs($user) : $this->assertGuest();
    }

    public static function invalidClaims(): array
    {
        return [
            [['email_verified' => false]], [['nonce' => 'another-session']], [['nonce' => null]],
            [['email' => 'someone@example.com']], [['email' => 'invalid']], [['aud' => 'another-client']],
            [['exp' => 1]], [['sub' => '']], [['exp' => null]],
        ];
    }

    #[DataProvider('invalidClaims')]
    public function test_invalid_identity_never_authenticates(array $overrides): void
    {
        $query = $this->begin();
        $this->fakeIdentity($this->claims($query['nonce'], $overrides));
        $this->get(route('google.callback', ['state' => $query['state'], 'code' => 'test-code']))
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $query = $this->begin();
        $this->fakeIdentity(false);
        $this->get(route('google.callback', ['state' => $query['state'], 'code' => 'test-code']))
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_workspace_identity_is_accepted(): void
    {
        $query = $this->begin();
        $this->fakeIdentity($this->claims($query['nonce'], ['email' => 'abogado@firma.example', 'hd' => 'firma.example']));
        $this->get(route('google.callback', ['state' => $query['state'], 'code' => 'test-code']))->assertRedirect(route('cliente.dashboard'));
        $this->assertDatabaseHas('usuarios', ['email' => 'abogado@firma.example', 'rol' => 'cliente']);
    }

    public function test_provider_failure_does_not_expose_exception_or_credentials(): void
    {
        $query = $this->begin();
        $client = Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('fetchAccessTokenWithAuthCode')->once()->andThrow(new \RuntimeException('PRIVATE_PROVIDER_RESPONSE'));
        $this->app->instance(Client::class, $client);
        $this->get(route('google.callback', ['state' => $query['state'], 'code' => 'test-code']))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertDontSee('PRIVATE_PROVIDER_RESPONSE');
        $this->assertGuest();
    }
}
