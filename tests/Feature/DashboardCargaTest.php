<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardCargaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_acota_pendientes_y_consultas_sin_mezclar_clientes(): void
    {
        $cliente = $this->usuario('cliente', 'cliente');
        $otro = $this->usuario('cliente', 'otro');
        $abogado = $this->usuario('abogado', 'abogado');
        foreach (range(1, 13) as $numero) {
            Cita::create([
                'codigo' => 'CARGA-'.$numero,
                'cliente_id' => $numero === 13 ? $otro->id : $cliente->id,
                'abogado_id' => $abogado->id,
                'fecha' => today()->addDays($numero),
                'hora_inicio' => '10:00:00',
                'hora_fin' => '11:00:00',
                'tipo' => 'consulta_general',
                'modalidad' => 'presencial',
                'estado' => $numero === 12 ? 'confirmada' : 'pendiente_pago',
                'monto' => 35,
            ]);
        }

        $this->actingAs($cliente);
        DB::enableQueryLog();
        $response = $this->get(route('cliente.dashboard'));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()
            ->assertViewHas('totalCitas', 12)
            ->assertViewHas('pendientes', 11)
            ->assertViewHas('completadas', 0)
            ->assertViewHas('citasPendPago', fn ($citas) => $citas->count() === 5)
            ->assertSee('11 cita(s) pendiente(s) de pago')
            ->assertSee('Ver todas mis citas');
        $this->assertLessThanOrEqual(5, count($queries), 'El dashboard no debe consultar por cada cita ni repetir los contadores.');
    }

    public function test_dashboard_vacio_muestra_contadores_en_cero(): void
    {
        $this->actingAs($this->usuario('cliente', 'nuevo'))
            ->get(route('cliente.dashboard'))->assertOk()
            ->assertViewHas('totalCitas', 0)
            ->assertViewHas('pendientes', 0)
            ->assertViewHas('completadas', 0)
            ->assertViewHas('citasPendPago', fn ($citas) => $citas->isEmpty());
    }

    private function usuario(string $rol, string $nombre): Usuario
    {
        return Usuario::create([
            'nombre' => $nombre,
            'email' => $nombre.'@example.test',
            'password' => 'ClaveDePrueba123',
            'rol' => $rol,
            'activo' => true,
        ]);
    }
}
