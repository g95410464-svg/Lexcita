<?php

namespace Tests\Integration;

use App\Support\OrganizationDatabaseProvisioner;
use App\Support\OrganizationDeployment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use PDO;
use PDOException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Destructive only to two randomly named databases on a local test server. */
class OrganizationPostgresTest extends TestCase
{
    private function connect(string $database, string $username, string $password): PDO
    {
        $port = getenv('PGTEST_PORT') ?: '55439';
        if (!ctype_digit($port)) {
            throw new \RuntimeException('Invalid local test port');
        }
        return new PDO("pgsql:host=127.0.0.1;port=$port;dbname=$database;sslmode=disable;connect_timeout=5", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    private function selectDatabase(string $database, string $id, string $password, bool $runtime): void
    {
        $this->refreshApplication();
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql' => [
                'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => getenv('PGTEST_PORT') ?: '55439',
                'database' => $database, 'username' => $database.($runtime ? '_app' : '_owner'),
                'password' => $password, 'charset' => 'utf8', 'prefix' => '', 'search_path' => 'public', 'sslmode' => 'disable',
            ],
            'app.url' => "https://$id.example.test",
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'organization.isolated' => $runtime,
            'organization.id' => $id,
            'organization.name' => "Organization $id",
            'organization.database' => $database,
        ]);
        app(OrganizationDeployment::class)->configure();
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme('https');
    }

    public function test_two_databases_reject_cross_access_and_keep_registration_sessions_and_cache_separate(): void
    {
        $adminPassword = getenv('PGTEST_ADMIN_PASSWORD');
        if (!$adminPassword || !extension_loaded('pdo_pgsql')) {
            $this->markTestSkipped('Requires pdo_pgsql and a local PostgreSQL test server with PGTEST_ADMIN_PASSWORD.');
        }
        $admin = $this->connect('postgres', 'lexcita_test_admin', $adminPassword);
        $suffix = bin2hex(random_bytes(5));
        $databases = ['alpha' => "lexcita_test_{$suffix}_a", 'bravo' => "lexcita_test_{$suffix}_b"];
        $ownerPassword = base64_encode(random_bytes(36));
        $appPassword = base64_encode(random_bytes(36))."'quoted";
        try {
            foreach ($databases as $id => $database) {
                if ($id === 'alpha') {
                    $environment = [
                        'PGHOST' => '127.0.0.1', 'PGPORT' => getenv('PGTEST_PORT') ?: '55439',
                        'PGUSER' => 'lexcita_test_admin', 'PGPASSWORD' => $adminPassword, 'PGSSLMODE' => 'disable',
                        'ORGANIZATION_DATABASE' => $database, 'ORGANIZATION_ID' => $id, 'ORGANIZATION_NAME' => "Organization $id",
                        'ORGANIZATION_OWNER_PASSWORD' => $ownerPassword, 'ORGANIZATION_APP_PASSWORD' => $appPassword,
                        'DB_HOST' => '127.0.0.1', 'DB_PORT' => getenv('PGTEST_PORT') ?: '55439', 'DB_SSLMODE' => 'disable',
                    ];
                    foreach (['provision-organization.php', 'migrate-organization.php'] as $script) {
                        $process = new Process([PHP_BINARY, '-d', 'extension=pdo_pgsql', base_path('scripts/'.$script)], base_path(), $environment);
                        $process->mustRun();
                        $this->assertSame(0, $process->getExitCode());
                    }
                    // Re-running provisioning must refuse to replace a database.
                    $process = new Process([PHP_BINARY, '-d', 'extension=pdo_pgsql', base_path('scripts/provision-organization.php')], base_path(), $environment);
                    $process->run();
                    $this->assertSame(1, $process->getExitCode());
                    $this->assertStringNotContainsString($appPassword, $process->getErrorOutput());
                    continue;
                }
                (new OrganizationDatabaseProvisioner)->provision($admin,
                    fn ($db) => $this->connect($db, 'lexcita_test_admin', $adminPassword),
                    $database, $id, "Organization $id", $ownerPassword, $appPassword);
                $this->selectDatabase($database, $id, $ownerPassword, false);
                $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            }
            foreach ($databases as $id => $database) {
                $other = $databases[$id === 'alpha' ? 'bravo' : 'alpha'];
                try {
                    $this->connect($other, $database.'_app', $appPassword);
                    $this->fail('Cross-organization database connection was accepted');
                } catch (PDOException $e) {
                    $this->assertStringContainsString('permission denied for database', $e->getMessage());
                }
                $pdo = $this->connect($database, $database.'_app', $appPassword);
                foreach (['CREATE TABLE public.forbidden (id integer)', 'TRUNCATE usuarios',
                    "UPDATE lexcita_organization SET id = 'other'", 'SET ROLE "'.$database.'_owner"'] as $sql) {
                    try {
                        $pdo->exec($sql);
                        $this->fail('Runtime role accepted a forbidden operation');
                    } catch (PDOException $e) {
                        $this->assertSame('42501', $e->getCode());
                    }
                }
                $pdo = null;
            }

            $this->selectDatabase($databases['alpha'], 'alpha', $appPassword, true);
            $this->artisan('organization:check')->assertSuccessful();
            $this->get('https://alpha.example.test/login')->assertOk()->assertSee('Organization alpha');
            $this->assertDatabaseCount('usuarios', 0);
            $payload = ['nombre' => 'Cliente Alpha', 'email' => 'same@example.test',
                'password' => 'Test-password-2026', 'password_confirmation' => 'Test-password-2026',
                'telefono_whatsapp' => '+503 7123 4567'];
            $this->post('https://alpha.example.test/registro', $payload)
                ->assertSessionHasNoErrors()->assertRedirect('https://alpha.example.test/cliente/dashboard');
            $alphaSession = session()->getId();
            Auth::forgetGuards();
            $this->get('https://alpha.example.test/cliente/dashboard')->assertOk();
            Cache::put('private-marker', 'alpha', 60);
            $this->assertDatabaseCount('usuarios', 1);

            $this->selectDatabase($databases['bravo'], 'bravo', $appPassword, true);
            $this->artisan('organization:check')->assertSuccessful();
            $this->assertDatabaseCount('usuarios', 0);
            $this->assertNull(Cache::get('private-marker'));
            // Even a session ID re-encrypted with Bravo's key cannot find an
            // authenticated Alpha session in Bravo's database.
            $this->withCookie(config('session.cookie'), $alphaSession)
                ->get('https://bravo.example.test/cliente/dashboard')->assertRedirect('https://bravo.example.test/login');
            $this->post('https://bravo.example.test/registro', [...$payload, 'nombre' => 'Cliente Bravo'])
                ->assertSessionHasNoErrors()->assertRedirect('https://bravo.example.test/cliente/dashboard');
            $this->assertDatabaseCount('usuarios', 1);
            $this->assertDatabaseHas('usuarios', ['nombre' => 'Cliente Bravo']);
            $this->assertDatabaseMissing('usuarios', ['nombre' => 'Cliente Alpha']);

            config(['organization.name' => 'Wrong organization']);
            $this->artisan('organization:check')->assertFailed();
        } finally {
            DB::disconnect('pgsql');
            $this->refreshApplication();
            foreach ($databases as $database) {
                $admin->exec('DROP DATABASE IF EXISTS "'.$database.'" WITH (FORCE)');
                $admin->exec('DROP ROLE IF EXISTS "'.$database.'_app"');
                $admin->exec('DROP ROLE IF EXISTS "'.$database.'_owner"');
            }
        }
    }
}
