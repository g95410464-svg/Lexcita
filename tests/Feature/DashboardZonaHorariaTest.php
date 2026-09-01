<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Services\CitaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE — Dashboard y zona horaria (America/El_Salvador).
 *
 * LexCita guarda citas.fecha y hora_inicio/hora_fin como hora local de
 * El Salvador (UTC-6). Por eso el dashboard debe calcular "hoy", "próximas"
 * y la ventana de videollamada en America/El_Salvador, no en UTC — si el
 * servidor corre en UTC, una cita confirmada por la noche salvadoreña queda
 * excluida (el "día UTC" ya es el siguiente). Estas pruebas fijan citas según
 * el "hoy" de la app (config/app.php timezone) y verifican que el dashboard
 * las agrupe correctamente.
 */
class DashboardZonaHorariaTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // La prueba depende de que la app calcule "hoy" en El Salvador.
        $this->assertSame('America/El_Salvador', config('app.timezone'));
    }

    private function makeUsuario(string $rol, string $prefijo): Usuario
    {
        $this->seq++;
        return Usuario::create([
            'nombre'   => ucfirst($rol) . ' ' . $prefijo . $this->seq,
            'email'    => $prefijo . $this->seq . '@test.com',
            'password' => 'password123',
            'rol'      => $rol,
            'activo'   => true,
        ]);
    }

    /** Cita confirmada en la fecha dada, presencial o virtual. */
    private function citaConfirmada(Usuario $cliente, Usuario $abogado, string $fecha, string $modalidad = 'presencial'): Cita
    {
        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => $fecha,
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => $modalidad,
            'descripcion' => 'Cita de prueba',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);

        app(CitaService::class)->confirmar($cita);
        $cita->refresh();

        return $cita;
    }

    public function test_cita_confirmada_de_hoy_aparece_en_dashboard_abogado(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $this->citaConfirmada($cliente, $abogado, today()->toDateString());

        $resp = $this->actingAs($abogado)->get(route('abogado.dashboard'));

        $resp->assertOk();
        $resp->assertSee($cliente->nombre);
        $resp->assertSee('Citas de Hoy');
    }

    public function test_cita_de_manana_aparece_en_proximas(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $this->citaConfirmada($cliente, $abogado, today()->addDay()->toDateString());

        $resp = $this->actingAs($abogado)->get(route('abogado.dashboard'));

        $resp->assertOk();
        $resp->assertSee($cliente->nombre);
        $resp->assertSee('Próximas Citas');
    }

    public function test_cita_pasada_no_aparece_en_proximas(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $this->citaConfirmada($cliente, $abogado, today()->subDays(2)->toDateString());

        $resp = $this->actingAs($abogado)->get(route('abogado.dashboard'));

        $resp->assertOk();
        $resp->assertDontSee($cliente->nombre);
    }

    public function test_boton_unirse_a_videollamada_visible_para_virtual_confirmada_con_videoroom(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $cita = $this->citaConfirmada($cliente, $abogado, today()->toDateString(), 'virtual');

        // La confirmación creó la sala de videollamada.
        $this->assertNotNull($cita->videoRoom);
        $token = $cita->videoRoom->room_token;

        $resp = $this->actingAs($abogado)->get(route('abogado.dashboard'));

        $resp->assertOk();
        // El botón enlaza a la sala de la cita de hoy.
        $resp->assertSee(route('video.sala', $token));
        $resp->assertSee('Unirse a videollamada');
    }

    public function test_cliente_tambien_ve_su_cita_de_hoy(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $this->citaConfirmada($cliente, $abogado, today()->toDateString());

        $resp = $this->actingAs($cliente)->get(route('cliente.dashboard'));

        $resp->assertOk();
        $resp->assertSee($abogado->nombre);
    }
}
