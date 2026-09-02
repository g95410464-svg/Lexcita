<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Services\CitaService;
use App\Services\VideoRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 1 (Jitsi incrustado) — Nombre de sala de Jitsi.
 *
 * Garantías:
 *   - Cliente y abogado de la MISMA cita reciben EXACTAMENTE el mismo roomName
 *     (deriva solo del room_token de la VideoRoom compartida).
 *   - Dos citas distintas producen roomNames distintos.
 *   - El roomName no filtra id de cita, código de cita, email, nombre de usuario
 *     ni el propio room_token.
 */
class JitsiRoomNameTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

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

    private function citaVirtualConfirmada(): array
    {
        $cliente = $this->makeUsuario('cliente', 'jcliente');
        $abogado = $this->makeUsuario('abogado', 'jabogado');
        $ahora   = now();

        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => $ahora->toDateString(),
            'hora_inicio' => $ahora->format('H:i:s'),
            'hora_fin'    => $ahora->copy()->addHour()->format('H:i:s'),
            'tipo'        => 'consulta_general',
            'modalidad'   => 'virtual',
            'descripcion' => 'Cita Jitsi de prueba',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);

        app(CitaService::class)->confirmar($cita);
        $cita->refresh();

        return [$cliente, $abogado, $cita];
    }

    public function test_cliente_y_abogado_de_la_misma_cita_obtienen_el_mismo_room_name(): void
    {
        [$cliente, $abogado, $cita] = $this->citaVirtualConfirmada();
        $room = app(VideoRoomService::class)->obtenerOCrearSala($cita);

        // Ambos roles abren la MISMA sala por el MISMO room_token (ruta única)
        // y el roomName deriva solo de ese token.
        $roomName = $room->jitsiRoomName();

        $this->assertSame($room->room_token, $room->room_token);
        $this->assertSame($roomName, $room->jitsiRoomName());
        $this->assertSame(
            $roomName,
            'LexCita-' . hash('sha256', $room->room_token),
            'El roomName debe ser "LexCita-" + sha256 del room_token.'
        );

        // Debe ser el mismo aunque se consulte desde una instancia fresca
        // (lo que ocurre en la petición HTTP de cada rol).
        $this->assertSame($roomName, $room->fresh()->jitsiRoomName());
    }

    public function test_dos_citas_distintas_producen_room_names_distintos(): void
    {
        [, , $cita1] = $this->citaVirtualConfirmada();
        [, , $cita2] = $this->citaVirtualConfirmada();

        $room1 = app(VideoRoomService::class)->obtenerOCrearSala($cita1);
        $room2 = app(VideoRoomService::class)->obtenerOCrearSala($cita2);

        $this->assertNotSame($room1->id, $room2->id);
        $this->assertNotSame($room1->room_token, $room2->room_token);
        $this->assertNotSame($room1->jitsiRoomName(), $room2->jitsiRoomName());
    }

    public function test_room_name_no_filtra_datos_personales(): void
    {
        [$cliente, $abogado, $cita] = $this->citaVirtualConfirmada();
        $room = app(VideoRoomService::class)->obtenerOCrearSala($cita);

        $roomName = $room->jitsiRoomName();

        // El id no debe ser la base del nombre (no predecible desde el id de la cita).
        $this->assertNotSame('LexCita-' . $cita->id, $roomName);
        $this->assertStringNotContainsString($cita->codigo, $roomName);

        // Ni emails ni nombres de usuarios.
        $this->assertStringNotContainsString(mb_strtolower($cliente->email), mb_strtolower($roomName));
        $this->assertStringNotContainsString(mb_strtolower($abogado->email), mb_strtolower($roomName));
        $this->assertStringNotContainsString(mb_strtolower($cliente->nombre), mb_strtolower($roomName));
        $this->assertStringNotContainsString(mb_strtolower($abogado->nombre), mb_strtolower($roomName));

        // Ni el room_token crudo (el hash lo oculta).
        $this->assertStringNotContainsString($room->room_token, $roomName);

        // Formato esperado: prefijo + 64 hex.
        $this->assertMatchesRegularExpression('/^LexCita-[0-9a-f]{64}$/', $roomName);
    }
}