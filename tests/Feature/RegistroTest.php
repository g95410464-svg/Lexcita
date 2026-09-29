<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    use RefreshDatabase;

    private function datos(): array
    {
        return [
            'nombre' => 'Cliente de prueba',
            'email' => 'registro@example.test',
            'password' => 'ClaveDePrueba123',
            'password_confirmation' => 'ClaveDePrueba123',
            'telefono_whatsapp' => '+503 7123 4567',
        ];
    }

    public function test_los_campos_del_formulario_crean_cuenta_y_abren_dashboard(): void
    {
        // Enviar los nombres de campo reales del HTML detecta desajustes con el controlador.
        $page = $this->get(route('registro'))->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
        $payload = [];
        foreach ($document->getElementsByTagName('input') as $input) {
            $name = $input->getAttribute('name');
            $payload[$name] = $this->datos()[$name] ?? $input->getAttribute('value');
        }
        $sessionId = session()->getId();

        $this->post(route('registro.post'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cliente.dashboard'));

        $usuario = Usuario::sole();
        $this->assertSame('cliente', $usuario->rol);
        $this->assertTrue($usuario->activo);
        $this->assertTrue(Hash::check($payload['password'], $usuario->password));
        $this->assertNotSame($sessionId, session()->getId());
        // Leer la identidad desde sesión, sin reutilizar el usuario en memoria del guard.
        Auth::forgetGuards();
        $this->get(route('cliente.dashboard'))->assertOk()->assertSee('Bienvenido, Cliente');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_contrasenas_distintas_muestran_error_y_conservan_datos_no_secretos(): void
    {
        $data = $this->datos();
        $data['password_confirmation'] = 'NoCoincide123';
        $this->from(route('registro'))->post(route('registro.post'), $data)
            ->assertRedirect(route('registro'))
            ->assertSessionHasErrors('password');

        $this->get(route('registro'))->assertOk()
            ->assertSee('Las contraseñas no coinciden.')
            ->assertSee('value="Cliente de prueba"', false)
            ->assertSee('value="registro@example.test"', false)
            ->assertSee('value="+503 7123 4567"', false)
            ->assertDontSee($data['password'])
            ->assertDontSee($data['password_confirmation']);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertGuest();
    }

    public function test_correo_duplicado_no_crea_otra_cuenta(): void
    {
        Usuario::create([...$this->datos(), 'rol' => 'cliente', 'activo' => true]);
        $this->from(route('registro'))->post(route('registro.post'), $this->datos())
            ->assertSessionHasErrors('email');
        $this->get(route('registro'))->assertSee('Este correo ya está registrado.');
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertGuest();
    }

    public function test_validacion_servidor_rechaza_campos_ausentes_y_clave_corta(): void
    {
        $this->post(route('registro.post'), [
            'email' => 'registro@example.test',
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertSessionHasErrors(['nombre', 'telefono_whatsapp', 'password']);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_la_cuenta_nueva_permite_cerrar_y_volver_a_iniciar_sesion(): void
    {
        $this->post(route('registro.post'), [...$this->datos(), 'rol' => 'admin', 'activo' => false])
            ->assertRedirect(route('cliente.dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.post'), [
            'email' => $this->datos()['email'],
            'password' => $this->datos()['password'],
        ])->assertRedirect(route('cliente.dashboard'));
        $this->get(route('cliente.dashboard'))->assertOk();
        $this->assertSame('cliente', Auth::user()->rol);
    }
}
