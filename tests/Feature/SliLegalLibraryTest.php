<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SliLegalLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): Usuario
    {
        return Usuario::create([
            'nombre' => ucfirst($role).' de prueba', 'email' => $role.'@example.test',
            'password' => 'ClaveLocal123', 'rol' => $role, 'activo' => true,
        ]);
    }

    private function enableSliViews(): void
    {
        config(['organization.id' => 'servicios-legales-integrados']);
        app(AppServiceProvider::class, ['app' => $this->app])->boot();
        app('view')->getFinder()->flush();
    }

    public function test_lawyer_role_gets_library_navigation_and_curated_public_sources(): void
    {
        $this->enableSliViews();
        $this->actingAs($this->user('abogado'));

        $this->get(route('abogado.biblioteca'))->assertOk()
            ->assertSee('Biblioteca legal')
            ->assertSee('Buscador oficial de legislación')
            ->assertSee('Constitución de la República')
            ->assertSee('Consultar fuente oficial');
        $this->get(route('abogado.dashboard'))->assertOk()
            ->assertSee('href="'.route('abogado.biblioteca').'"', false);
    }

    public function test_admin_can_publish_https_resource_and_clients_cannot_open_library(): void
    {
        $this->enableSliViews();
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('interno.biblioteca.store'), [
            'title' => 'Código de prueba', 'category' => 'Civil', 'jurisdiction' => 'El Salvador',
            'source_url' => 'https://www.asamblea.gob.sv/leyes-y-decretos/decretos-por-anios',
            'description' => 'Fuente de consulta',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('sli_library_documents', [
            'title' => 'Código de prueba', 'created_by' => $admin->id,
        ]);
        $this->actingAs($this->user('cliente'))->get(route('abogado.biblioteca'))->assertRedirect(route('cliente.dashboard'));
    }
}
