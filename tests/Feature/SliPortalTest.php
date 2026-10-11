<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SliPortalTest extends TestCase
{
    use RefreshDatabase;

    private function enableSli(): void
    {
        config(['organization.id' => 'servicios-legales-integrados']);
        app(AppServiceProvider::class, ['app' => $this->app])->boot();
        app('view')->getFinder()->flush();
    }

    private function user(string $role): Usuario
    {
        return Usuario::create([
            'nombre' => ucfirst($role).' de prueba', 'email' => $role.'@example.test',
            'password' => 'ClaveLocal123', 'rol' => $role, 'activo' => true,
        ]);
    }

    public function test_theme_is_selected_by_server_configuration_only(): void
    {
        config(['organization.id' => 'otra-organizacion']);
        $this->get('/login?organization=servicios-legales-integrados')
            ->assertOk()->assertDontSee('data-portal="sli"', false);
        $this->enableSli();
        $this->get('/login')->assertOk()->assertSee('data-portal="sli"', false)
            ->assertSee('images/sli-firma.png')->assertDontSee('cdn.tailwindcss.com');
    }

    public function test_public_landing_is_exclusive_to_sli_and_keeps_authentication_links(): void
    {
        foreach ([null, 'otra-organizacion'] as $organization) {
            config(['organization.id' => $organization]);
            $this->get('/?organization=servicios-legales-integrados')->assertRedirect(route('login'));
        }

        $this->enableSli();
        $this->get(route('home'))->assertOk()->assertSee('Tu gestión legal,')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('sli.start').'"', false)
            ->assertDontSee('name="password"', false);
        $this->get(route('login'))->assertOk()
            ->assertSee('action="'.route('login.post').'"', false)
            ->assertSee('href="'.route('home').'"', false);
        $this->get(route('cliente.dashboard'))->assertRedirect(route('login'));
    }

    public function test_actual_sli_registration_fields_create_client_and_persist_session(): void
    {
        $this->enableSli();
        $page = $this->get(route('registro'))->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
        $data = ['nombre' => 'Cliente nuevo', 'email' => 'registro@example.test',
            'password' => 'ClaveLocal123', 'password_confirmation' => 'ClaveLocal123',
            'telefono_whatsapp' => '+503 7000 0000'];
        $payload = [];
        foreach ($document->getElementsByTagName('input') as $input) {
            $name = $input->getAttribute('name');
            $payload[$name] = $data[$name] ?? $input->getAttribute('value');
        }
        $this->post(route('registro.post'), $payload)->assertSessionHasNoErrors()
            ->assertRedirect(route('cliente.dashboard'));
        Auth::forgetGuards();
        $this->get(route('cliente.dashboard'))->assertOk()->assertSee('Bienvenido, Cliente')
            ->assertSee('data-portal="sli"', false);
        $this->assertSame('cliente', Usuario::sole()->rol);
    }

    public function test_validation_keeps_non_secret_input_and_displays_errors(): void
    {
        $this->enableSli();
        $this->from('/registro')->post('/registro', [
            'nombre' => 'Nombre prueba', 'email' => 'registro@example.test',
            'telefono_whatsapp' => '+503 7000 0000',
            'password' => 'ClaveLocal123', 'password_confirmation' => 'OtraClave123',
        ])->assertSessionHasErrors('password');
        $this->get('/registro')->assertOk()->assertSee('Las contraseñas no coinciden.')
            ->assertSee('value="Nombre prueba"', false)->assertDontSee('ClaveLocal123');
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_all_role_workflows_render_without_exposing_other_role_navigation(): void
    {
        $this->enableSli();
        foreach (['cliente' => ['dashboard', 'nueva-cita', 'mis-citas'],
            'abogado' => ['dashboard', 'agenda'],
            'interno' => ['dashboard', 'abogados', 'clientes', 'citas']] as $prefix => $pages) {
            $this->actingAs($this->user($prefix === 'interno' ? 'admin' : $prefix));
            foreach ($pages as $page) {
                $response = $this->get(route($prefix.'.'.$page))->assertOk()
                    ->assertSee('data-portal="sli"', false)->assertDontSee('cdn.tailwindcss.com');
                if ($prefix === 'cliente') {
                    $response->assertDontSee('href="'.route('interno.abogados').'"', false);
                }
            }
        }
        // The production statistics query uses PostgreSQL; exercise the view separately on SQLite.
        $html = view('interno.estadisticas', ['ingresosMes' => collect([(object) ['mes' => 10, 'anio' => 2026, 'total' => 35]])])->render();
        $this->assertStringContainsString('$35', $html);
        $this->actingAs(Usuario::where('rol', 'cliente')->first());
        $this->get(route('interno.dashboard'))->assertRedirect(route('cliente.dashboard'));
    }

    public function test_payment_and_ticket_keep_ownership_and_honest_payment_state(): void
    {
        $this->enableSli();
        config(['services.paypal.client_id' => null]);
        $client = $this->user('cliente');
        $cita = Cita::create([
            'codigo' => Cita::generarCodigo(), 'cliente_id' => $client->id,
            'abogado_id' => $this->user('abogado')->id, 'fecha' => today()->addDay(),
            'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'tipo' => 'consulta_general',
            'modalidad' => 'presencial', 'estado' => 'pendiente_pago', 'monto' => 35,
        ]);
        $this->actingAs($client)->get(route('cliente.pre-confirmacion', $cita))->assertOk()
            ->assertSee('El pago en línea aún no está disponible')->assertDontSee('www.paypal.com/sdk');
        $this->get(route('cliente.ticket', $cita))->assertNotFound();
        $cita->update(['estado' => 'confirmada']);
        $this->get(route('cliente.ticket', $cita))->assertOk()->assertSee($cita->codigo)
            ->assertSee('Confirmada')->assertDontSee('grado militar');
        $other = Usuario::create(['nombre' => 'Otra persona', 'email' => 'otro@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'cliente', 'activo' => true]);
        $this->actingAs($other)->get(route('cliente.ticket', $cita))->assertNotFound();
        $this->get(route('cliente.pre-confirmacion', $cita))->assertNotFound();
    }

    public function test_sli_video_page_preserves_participant_access_and_room_configuration(): void
    {
        $this->enableSli();
        $this->travelTo(now()->setTime(12, 0));
        config(['jitsi.provider' => 'public']);
        $client = $this->user('cliente');
        $lawyer = $this->user('abogado');
        $cita = Cita::create([
            'codigo' => Cita::generarCodigo(), 'cliente_id' => $client->id,
            'abogado_id' => $lawyer->id, 'fecha' => today(),
            'hora_inicio' => '12:00:00', 'hora_fin' => '13:00:00', 'tipo' => 'consulta_general',
            'modalidad' => 'virtual', 'estado' => 'pendiente_pago', 'monto' => 35,
        ]);
        app(\App\Services\CitaService::class)->confirmar($cita);
        $room = $cita->fresh()->videoRoom;
        foreach ([$client, $lawyer] as $participant) {
            $this->actingAs($participant)->get(route('video.sala', $room->room_token))
                ->assertOk()->assertSee('data-portal="sli"', false)
                ->assertSee('JitsiMeetExternalAPI')->assertSee('id="datos-sala"', false);
        }
        $this->actingAs($this->user('admin'))->get(route('video.sala', $room->room_token))
            ->assertForbidden();
    }
    public function test_public_directory_only_exposes_active_lawyer_profiles(): void
    {
        $this->enableSli();
        $lawyer = $this->user('abogado');
        $lawyer->update(['especialidad' => 'Derecho familiar', 'telefono_whatsapp' => '70123456']);
        $client = $this->user('cliente');
        $admin = $this->user('admin');
        $inactive = Usuario::create(['nombre' => 'Oculto', 'email' => 'oculto@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'abogado', 'activo' => false]);
        $this->get('/abogados')->assertOk()->assertSee($lawyer->nombre)->assertSee('Derecho familiar')
            ->assertDontSee($client->nombre)->assertDontSee($admin->nombre)->assertDontSee('Oculto')
            ->assertDontSee($lawyer->email)->assertDontSee('70123456');
        $this->get('/abogados/'.$lawyer->id)->assertOk()->assertSee('Derecho familiar')
            ->assertDontSee($lawyer->email)->assertDontSee('70123456');
        foreach ([$client->id, $admin->id, $inactive->id, 999999] as $id) {
            $this->get('/abogados/'.$id)->assertNotFound();
            $this->get('/abogados/'.$id.'/agendar')->assertNotFound();
        }
        foreach (['/conocenos', '/como-funciona'] as $path) {
            $this->get($path)->assertOk();
        }
        config(['organization.id' => 'otra-organizacion']);
        foreach (['/conocenos', '/como-funciona', '/abogados', '/agendar', '/abogados/'.$lawyer->id] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_booking_survives_registration_and_selects_the_public_profile(): void
    {
        $this->enableSli();
        $lawyer = $this->user('abogado');
        $this->get('/abogados/'.$lawyer->id.'/agendar')->assertRedirect(route('registro'));
        $this->get('/registro')->assertOk()->assertSee('Continuar con Google')
            ->assertSee('href="'.route('google.redirect').'"', false);
        $this->post('/registro', ['nombre' => 'Cliente nuevo', 'email' => 'nuevo@example.test',
            'password' => 'ClaveLocal123', 'password_confirmation' => 'ClaveLocal123',
            'telefono_whatsapp' => '+50370000000'])
            ->assertRedirect(route('cliente.nueva-cita', ['abogado' => $lawyer->id]));
        $this->get(route('cliente.nueva-cita', ['abogado' => $lawyer->id]))->assertOk()
            ->assertViewHas('abogadoSeleccionado', fn ($selected) => $selected->id === $lawyer->id);
        $lawyer->update(['activo' => false]);
        $this->get(route('cliente.nueva-cita', ['abogado' => $lawyer->id]))->assertOk()
            ->assertViewHas('abogadoSeleccionado', null);
    }

    public function test_booking_survives_google_registration(): void
    {
        $this->enableSli();
        $lawyer = $this->user('abogado');
        $this->get('/abogados/'.$lawyer->id.'/agendar')->assertRedirect(route('registro'));
        $state = str_repeat('a', 64);
        $this->mock(\App\Services\GoogleLoginService::class, function ($mock) {
            $mock->shouldReceive('identity')->once()->with('test-code', 'test-nonce')
                ->andReturn(['email' => 'google@example.test', 'nombre' => 'Cliente Google']);
        });
        $this->withSession(['google.oauth' => ['state' => $state, 'nonce' => 'test-nonce',
            'expires_at' => now()->addMinutes(10)->timestamp, 'redirect_uri' => config('google.redirect_uri')]])
            ->get('/auth/google/callback?state='.$state.'&code=test-code')
            ->assertRedirect(route('cliente.nueva-cita', ['abogado' => $lawyer->id]));
        $this->assertDatabaseHas('usuarios', ['email' => 'google@example.test', 'rol' => 'cliente']);
    }

    public function test_booking_login_revalidates_lawyer_and_preserves_role_routes(): void
    {
        $this->enableSli();
        $lawyer = $this->user('abogado');
        $client = $this->user('cliente');
        $this->get('/abogados/'.$lawyer->id.'/agendar')->assertRedirect(route('registro'));
        $lawyer->update(['activo' => false]);
        $this->post('/login', ['email' => $client->email, 'password' => 'ClaveLocal123'])
            ->assertRedirect(route('cliente.nueva-cita'));
        $this->assertNull(session('sli.booking_lawyer'));
        $this->actingAs($this->user('admin'))->get('/agendar')->assertRedirect(route('interno.dashboard'));
    }
}
