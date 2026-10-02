<?php

namespace Tests\Feature;

use App\Models\{Cita, Usuario};
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlInputSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, string $name): Usuario
    {
        return Usuario::create([
            'nombre' => $name, 'email' => $name.'@example.test',
            'password' => 'PasswordPrueba123', 'rol' => $rol, 'activo' => true,
        ]);
    }

    private function cita(Usuario $cliente, Usuario $abogado, string $codigo, string $estado = 'pendiente_pago'): Cita
    {
        return Cita::create([
            'codigo' => $codigo, 'cliente_id' => $cliente->id, 'abogado_id' => $abogado->id,
            'fecha' => '2026-10-15', 'hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00',
            'tipo' => 'consulta_general', 'modalidad' => 'presencial', 'monto' => 35,
            'estado' => $estado,
        ]);
    }

    public function test_sql_strings_cannot_bypass_login(): void
    {
        $user = $this->usuario('cliente', 'cliente');
        $this->postJson('/login', ['email' => "' OR 1=1 --", 'password' => 'PasswordPrueba123'])
            ->assertUnprocessable();
        $this->post('/login', ['email' => $user->email, 'password' => "' OR 1=1 --"])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_search_binds_sql_payload_as_data(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $cliente = $this->usuario('cliente', 'cliente');
        $abogado = $this->usuario('abogado', 'abogado');
        $this->cita($cliente, $abogado, 'CITA-SECRETA');
        $payload = "' OR 1=1 --";
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries) { $queries[] = $query; });
        $this->actingAs($admin)->get('/interno/citas?'.http_build_query(['buscar' => $payload]))
            ->assertOk()->assertViewHas('citas', fn ($citas) => $citas->total() === 0);
        $boundSearch = false;
        foreach ($queries as $query) {
            $this->assertStringNotContainsString($payload, $query->sql);
            $boundSearch = $boundSearch || in_array('%'.$payload.'%', $query->bindings, true);
        }
        $this->assertTrue($boundSearch, 'Search must use parameter bindings.');
        $this->assertDatabaseCount('citas', 1);
    }

    public function test_search_cannot_bypass_state_or_lawyer_filters(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $cliente = $this->usuario('cliente', 'cliente');
        $abogado = $this->usuario('abogado', 'abogado');
        $otro = $this->usuario('abogado', 'otro');
        $allowed = $this->cita($cliente, $abogado, 'MATCH-OK', 'confirmada');
        $this->cita($cliente, $abogado, 'MATCH-PENDIENTE');
        $this->cita($cliente, $otro, 'MATCH-AJENO', 'confirmada');
        $this->actingAs($admin)->get('/interno/citas?'.http_build_query([
            'buscar' => 'MATCH', 'estado' => 'confirmada', 'abogado_id' => $abogado->id,
        ]))->assertOk()->assertViewHas('citas', fn ($citas) => $citas->modelKeys() === [$allowed->id]);
    }

    public function test_malformed_filters_are_rejected_before_database_coercion(): void
    {
        $this->actingAs($this->usuario('admin', 'admin'));
        foreach ([['abogado_id' => '1 OR 1=1'], ['estado' => "' OR 1=1 --"], ['buscar' => ['unexpected']]] as $filter) {
            $this->getJson('/interno/citas?'.http_build_query($filter))->assertUnprocessable();
        }
    }

    public function test_slots_reject_sql_ids_and_users_who_are_not_active_lawyers(): void
    {
        $cliente = $this->usuario('cliente', 'cliente');
        $inactive = $this->usuario('abogado', 'inactivo');
        $inactive->update(['activo' => false]);
        $this->actingAs($cliente);
        foreach (['1 OR 1=1', $cliente->id, $inactive->id] as $id) {
            $this->getJson('/api/slots?'.http_build_query(['abogado_id' => $id, 'fecha' => '2026-10-15']))
                ->assertUnprocessable()->assertJsonValidationErrors('abogado_id');
        }
        $this->postJson('/cliente/nueva-cita', [
            'abogado_id' => $cliente->id, 'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '10:00', 'tipo' => 'consulta_general', 'modalidad' => 'presencial',
        ])->assertUnprocessable()->assertJsonValidationErrors('abogado_id');
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_malformed_path_ids_are_404_instead_of_php_errors(): void
    {
        $this->actingAs($this->usuario('cliente', 'cliente'));
        $this->get('/cliente/ticket/'.rawurlencode('1 OR 1=1'))->assertNotFound();
        $this->get('/cliente/pre-confirmacion/abc')->assertNotFound();
    }

    public function test_paypal_callbacks_reject_array_tokens(): void
    {
        $this->actingAs($this->usuario('cliente', 'cliente'));
        $this->getJson('/cliente/paypal/return?token[]=invalid')->assertUnprocessable();
        $this->getJson('/cliente/paypal/cancel?token[]=invalid')->assertUnprocessable();
    }

    public function test_client_cannot_read_or_cancel_another_clients_appointment(): void
    {
        $dueno = $this->usuario('cliente', 'dueno');
        $ajeno = $this->usuario('cliente', 'ajeno');
        $abogado = $this->usuario('abogado', 'abogado');
        $cita = $this->cita($dueno, $abogado, 'CITA-AJENA');
        $this->actingAs($ajeno)->get('/cliente/pre-confirmacion/'.$cita->id)->assertNotFound();
        $this->post('/cliente/cancelar/'.$cita->id)->assertNotFound();
        $this->assertSame('pendiente_pago', $cita->fresh()->estado);
        $this->get('/cliente/mis-citas')->assertOk()->assertDontSee('CITA-AJENA');
    }
}
