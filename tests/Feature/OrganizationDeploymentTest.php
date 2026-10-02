<?php

namespace Tests\Feature;

use App\Support\OrganizationDeployment;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationDeploymentTest extends TestCase
{
    private function profile(): OrganizationDeployment
    {
        config([
            'organization.isolated' => true,
            'organization.id' => 'servicios-legales-integrados',
            'organization.name' => 'Servicios Legales Integrados S.A. de C.V.',
            'organization.database' => 'lexcita_sli',
            'app.url' => 'https://sli.example.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.database' => 'lexcita_sli',
            'database.connections.pgsql.username' => 'lexcita_sli_app',
        ]);
        return app(OrganizationDeployment::class);
    }

    public function test_wrong_host_is_rejected_before_database_or_session_access(): void
    {
        $this->profile()->configure();
        DB::shouldReceive('connection')->never();
        $this->withHeaders(['X-Organization' => 'servicios-legales-integrados', 'X-Forwarded-Host' => 'sli.example.test'])
            ->post('https://other.example.test/registro', ['organization_id' => 'servicios-legales-integrados'])
            ->assertStatus(421)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_internal_health_check_works_without_opening_a_session(): void
    {
        $this->profile()->configure();
        $this->get('http://healthcheck.railway.app/up')->assertOk()->assertCookieMissing(config('session.cookie'));
    }

    public function test_profile_uses_its_own_database_for_state_and_host_only_cookie(): void
    {
        $profile = $this->profile();
        config(['session.domain' => '.example.test', 'session.connection' => 'other', 'cache.limiter' => 'redis', 'queue.connections.database.connection' => 'other']);
        $profile->configure();
        $this->assertSame('pgsql', config('session.connection'));
        $this->assertSame('pgsql', config('cache.stores.database.connection'));
        $this->assertSame('pgsql', config('queue.connections.database.connection'));
        $this->assertSame('database', config('cache.limiter'));
        $this->assertSame('__Host-lexcita-servicios-legales-integrados-session', config('session.cookie'));
        $this->assertTrue(config('session.secure'));
        $this->assertNull(config('session.domain'));
        $this->assertSame(['sli.example.test'], config('reverb.apps.apps.0.allowed_origins'));
    }

    #[DataProvider('invalidConfigurations')]
    public function test_invalid_profile_fails_closed(string $key, mixed $value): void
    {
        $profile = $this->profile();
        config([$key => $value]);
        $this->expectException(\RuntimeException::class);
        $profile->configure();
    }

    public static function invalidConfigurations(): array
    {
        return [
            ['organization.id', '../../another'],
            ['organization.database', 'lexcita_sli; DROP DATABASE postgres'],
            ['organization.name', ''],
            ['app.url', 'http://sli.example.test'],
            ['app.url', 'https://user@sli.example.test'],
            ['app.url', 'https://sli.example.test/another'],
            ['database.connections.pgsql.username', 'postgres'],
            ['database.connections.pgsql.database', 'lexcita_other'],
            ['database.connections.pgsql.url', 'postgresql://lexcita_sli_app:password@localhost/lexcita_other'],
            ['database.connections.pgsql.url', 'postgresql://lexcita_sli_app:password@localhost/lexcita_sli?username=postgres'],
            ['database.connections.pgsql.read', ['host' => 'other-server']],
        ];
    }

    public function test_disabled_profile_preserves_existing_installation(): void
    {
        $before = config()->all();
        app(OrganizationDeployment::class)->configure();
        $this->assertSame($before, config()->all());
        $this->get('/login')->assertOk();
    }
}
