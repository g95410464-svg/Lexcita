<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use App\Models\VideoParticipant;
use App\Services\CitaService;
use App\Services\VideoRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitaServiceConfirmarTest extends TestCase
{
    use RefreshDatabase;

    private function makeCliente(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Cliente Test',
            'email'    => 'cliente@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
    }

    private function makeAbogado(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Abogado Test',
            'email'    => 'abogado@test.com',
            'password' => 'password123',
            'rol'      => 'abogado',
            'activo'   => true,
        ]);
    }

    private function makeCita(string $modalidad, string $estado = 'pendiente_pago'): Cita
    {
        return Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $this->makeCliente()->id,
            'abogado_id'  => $this->makeAbogado()->id,
            'fecha'       => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => $modalidad,
            'descripcion' => 'Cita de prueba',
            'estado'      => $estado,
            'monto'       => 50.00,
        ]);
    }

    /** 1. Confirmar cita presencial → confirmada y sin VideoRoom. */
    public function test_confirmar_cita_presencial_no_crea_video_room(): void
    {
        $cita = $this->makeCita('presencial');

        app(CitaService::class)->confirmar($cita);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame('confirmada', $cita->estado);
        $this->assertNull($cita->videoRoom);
        $this->assertSame(0, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** 2. Confirmar cita virtual → confirmada y crea exactamente una VideoRoom. */
    public function test_confirmar_cita_virtual_crea_una_video_room(): void
    {
        $cita = $this->makeCita('virtual');

        app(CitaService::class)->confirmar($cita);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertNotNull($cita->videoRoom);

        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
        $this->assertSame('programada', $cita->videoRoom->status);

        // Pre-registra ambos participantes
        $this->assertSame(2, VideoParticipant::where('room_id', $cita->videoRoom->id)->count());
    }

    /** 3. Confirmar nuevamente una cita virtual no crea duplicado. */
    public function test_confirmar_dos_veces_no_crea_duplicado(): void
    {
        $cita = $this->makeCita('virtual');

        // Primera confirmación
        app(CitaService::class)->confirmar($cita);

        // Segunda confirmación: el servicio debe rechazar (ya no está pendiente de pago)
        $this->expectException(\InvalidArgumentException::class);
        app(CitaService::class)->confirmar($cita);

        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** 3b. VideoRoomService::obtenerOCrearSala es idempotente. */
    public function test_obtener_ocrear_sala_es_idempotente(): void
    {
        $cita = $this->makeCita('virtual');
        app(CitaService::class)->confirmar($cita);

        $service = app(VideoRoomService::class);
        $room1 = $service->obtenerOCrearSala($cita);
        $room2 = $service->obtenerOCrearSala($cita);

        $this->assertSame($room1->id, $room2->id);
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** 4. Intentar confirmar una cita inválida (cancelada) → rechaza. */
    public function test_confirmar_cita_cancelada_lanza_excepcion(): void
    {
        $cita = $this->makeCita('virtual', 'cancelada');

        $this->expectException(\InvalidArgumentException::class);
        app(CitaService::class)->confirmar($cita);

        $this->assertSame(0, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** 5. Verificar relación Cita → VideoRoom. */
    public function test_relacion_cita_video_room(): void
    {
        $cita = $this->makeCita('virtual');
        app(CitaService::class)->confirmar($cita);

        $cita->refresh();
        $this->assertInstanceOf(VideoRoom::class, $cita->videoRoom);
        $this->assertSame($cita->id, $cita->videoRoom->cita_id);

        // Relación inversa
        $this->assertSame($cita->id, $cita->videoRoom->cita->id);
    }

    /** 6. Usuario no autorizado no puede confirmar una cita. */
    public function test_usuario_no_autorizado_no_puede_confirmar(): void
    {
        $cita = $this->makeCita('virtual');

        // Un cliente autenticado intenta la acción de admin (confirmar)
        $cliente = Usuario::where('rol', 'cliente')->first();
        $response = $this->actingAs($cliente)
            ->post(route('interno.citas.confirmar', $cita->id));

        // Redirige (302) fuera de la acción; la cita sigue pendiente
        $response->assertStatus(302);
        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertSame(0, VideoRoom::where('cita_id', $cita->id)->count());
    }
}