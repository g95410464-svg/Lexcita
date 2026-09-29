<?php

namespace Tests\Feature;

use App\Models\HorarioDisponible;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlotsRevalidacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_disponibilidad_anterior_no_permite_reservar_un_horario_ocupado(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(8, 0));
        $cliente = Usuario::create([
            'nombre' => 'Cliente', 'email' => 'cliente@example.test',
            'password' => 'ClavePrueba123', 'rol' => 'cliente', 'activo' => true,
        ]);
        $abogado = Usuario::create([
            'nombre' => 'Abogado', 'email' => 'abogado@example.test',
            'password' => 'ClavePrueba123', 'rol' => 'abogado', 'activo' => true,
        ]);
        HorarioDisponible::create([
            'abogado_id' => $abogado->id, 'dia_semana' => 'jueves',
            'hora_inicio' => '09:00:00', 'hora_fin' => '17:00:00', 'activo' => true,
        ]);
        $this->actingAs($cliente)->getJson(route('api.slots', [
            'abogado_id' => $abogado->id, 'fecha' => '2026-10-01',
        ]))->assertOk()->assertJsonFragment(['hora' => '10:00']);

        $datos = [
            'abogado_id' => $abogado->id, 'fecha' => '2026-10-01',
            'hora_inicio' => '10:00', 'tipo' => 'consulta_general', 'modalidad' => 'presencial',
        ];
        // PostgreSQL almacena DATE sin hora. Insertar el mismo formato en SQLite
        // evita que el cast de Eloquent introduzca un timestamp en esta fixture.
        DB::table('citas')->insert([
            ...$datos, 'codigo' => 'SLOT-OCUPADO', 'cliente_id' => $cliente->id,
            'hora_fin' => '11:00', 'estado' => 'pendiente_pago', 'monto' => 35,
        ]);

        $this->from(route('cliente.nueva-cita'))->post(route('cliente.nueva-cita.post'), $datos)
            ->assertRedirect(route('cliente.nueva-cita'))
            ->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('citas', 1);
    }
}
