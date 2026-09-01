<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use App\Services\CitaService;
use App\Services\VideoRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FASE 22 — Seguridad de la sala de videollamada.
 * FASE 23 — Ambos roles abren la MISMA sala (misma VideoRoom, mismo token).
 *
 * La autorización es SIEMPRE server-side (VideoRoomService::validarAcceso):
 *   - pertenencia a la cita (cliente o abogado)
 *   - cita confirmada
 *   - modalidad virtual
 *   - dentro de la ventana de tiempo
 *
 * GET de sala NUNCA crea una VideoRoom (solo la muestra si existe por token).
 */
class VideoRoomAccesoTest extends TestCase
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

    /**
     * Cita virtual confirmada con VideoRoom DENTRO de la ventana temporal abierta
     * (hora_inicio = ahora, hora_fin = ahora+1h → ventana [ahora-15m, ahora+1h]).
     * Devuelve [cliente, abogado, cita].
     */
    private function citaVirtualConfirmadaEnVentana(): array
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
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
            'descripcion' => 'Cita virtual de prueba',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);

        app(CitaService::class)->confirmar($cita);
        $cita->refresh();

        // Confirmado que NO se creó más de una sala.
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());

        return [$cliente, $abogado, $cita];
    }

    /**
     * Fuerza la creación de una sala para CUALQUIER cita (confirmada, presencial,
     * pendiente, cancelada...), para poder probar los caminos de rechazo sin que
     * el flujo de confirmación cree la sala.
     */
    private function forzarSala(Cita $cita): VideoRoom
    {
        return app(VideoRoomService::class)->crearSala($cita);
    }

    // ─── Acceso positivo ─────────────────────────────────────────

    /** 1. Cliente propietario puede abrir la sala. */
    public function test_cliente_propietario_puede_abrir_sala(): void
    {
        [$cliente, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        $this->actingAs($cliente)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(200);
    }

    /** 2. Abogado asignado puede abrir la sala. */
    public function test_abogado_asignado_puede_abrir_sala(): void
    {
        [, $abogado, $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        $this->actingAs($abogado)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(200);
    }

    // ─── Acceso denegado ─────────────────────────────────────────

    /** 3. Cliente ajeno → rechazado (403). */
    public function test_cliente_ajeno_rechazado(): void
    {
        [$cliente, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $ajeno = $this->makeUsuario('cliente', 'ajeno-cliente');
        $room  = $cita->videoRoom;

        $this->actingAs($ajeno)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(403);
    }

    /** 4. Abogado ajeno → rechazado (403). */
    public function test_abogado_ajeno_rechazado(): void
    {
        [, $abogado, $cita] = $this->citaVirtualConfirmadaEnVentana();
        $ajeno = $this->makeUsuario('abogado', 'ajeno-abogado');
        $room  = $cita->videoRoom;

        $this->actingAs($ajeno)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(403);
    }

    /** 5. Usuario no autenticado → redirige a login. */
    public function test_usuario_no_autenticado_redirige_a_login(): void
    {
        [, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        $this->get(route('video.sala', $room->room_token))
            ->assertRedirect(route('login'));
    }

    /** 6. Cita presencial → rechazado (aunque tenga sala forzada). */
    public function test_cita_presencial_rechazada(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $ahora   = now();

        // Confirmada, pero presencial.
        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => $ahora->toDateString(),
            'hora_inicio' => $ahora->format('H:i:s'),
            'hora_fin'    => $ahora->copy()->addHour()->format('H:i:s'),
            'tipo'        => 'consulta_general',
            'modalidad'   => 'presencial',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);
        app(CitaService::class)->confirmar($cita);
        $cita->refresh();

        // Forzamos una sala (en realidad confirmar no crea sala para presencial).
        $room = $this->forzarSala($cita);

        $this->actingAs($cliente)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(403);
    }

    /** 7. Cita pendiente → rechazado. */
    public function test_cita_pendiente_rechazada(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');

        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => now()->toDateString(),
            'hora_inicio' => now()->format('H:i:s'),
            'hora_fin'    => now()->copy()->addHour()->format('H:i:s'),
            'tipo'        => 'consulta_general',
            'modalidad'   => 'virtual',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);

        $room = $this->forzarSala($cita);

        $this->actingAs($cliente)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(403);
    }

    /** 8. Cita cancelada → rechazado. */
    public function test_cita_cancelada_rechazada(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');

        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => now()->toDateString(),
            'hora_inicio' => now()->format('H:i:s'),
            'hora_fin'    => now()->copy()->addHour()->format('H:i:s'),
            'tipo'        => 'consulta_general',
            'modalidad'   => 'virtual',
            'estado'      => 'cancelada',
            'monto'       => 35.00,
        ]);

        $room = $this->forzarSala($cita);

        $this->actingAs($cliente)
            ->get(route('video.sala', $room->room_token))
            ->assertStatus(403);
    }

    /** 9. room_token inválido → 404. */
    public function test_room_token_invalido_devuelve_404(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');

        $this->actingAs($cliente)
            ->get(route('video.sala', 'token-que-no-existe'))
            ->assertStatus(404);
    }

    // ─── Signaling HTTP (FASE 8 / 9 / 22) ─────────────────────────

    /** 10. Cliente autorizado puede enviar ANSWER. */
    public function test_cliente_autorizado_puede_enviar_answer(): void
    {
        [$cliente, $abogado, $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        $this->actingAs($cliente)
            ->postJson(route('video.answer', $room->room_token), [
                'sdp'            => ['type' => 'answer', 'sdp' => 'v=0\r\no=- 1 2 IN IP4 127.0.0.1\r\ns=-\r\n'],
                'target_user_id' => $abogado->id,
            ])
            ->assertOk();
    }

    /** 11. Abogado autorizado puede enviar OFFER. */
    public function test_abogado_autorizado_puede_enviar_offer(): void
    {
        [$cliente, $abogado, $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        $this->actingAs($abogado)
            ->postJson(route('video.offer', $room->room_token), [
                'sdp'            => ['type' => 'offer', 'sdp' => 'v=0\r\no=- 1 2 IN IP4 127.0.0.1\r\ns=-\r\n'],
                'target_user_id' => $cliente->id,
            ])
            ->assertOk();
    }

    /** 12. Usuario ajeno NO puede enviar OFFER. */
    public function test_usuario_ajeno_no_puede_enviar_offer(): void
    {
        [, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $ajeno = $this->makeUsuario('cliente', 'ajeno-cliente');
        $room  = $cita->videoRoom;

        $this->actingAs($ajeno)
            ->postJson(route('video.offer', $room->room_token), [
                'sdp'            => ['type' => 'offer', 'sdp' => 'x'],
                'target_user_id' => $cita->abogado_id,
            ])
            ->assertStatus(403);
    }

    /** 13. Usuario ajeno NO puede enviar ANSWER. */
    public function test_usuario_ajeno_no_puede_enviar_answer(): void
    {
        [, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $ajeno = $this->makeUsuario('abogado', 'ajeno-abogado');
        $room  = $cita->videoRoom;

        $this->actingAs($ajeno)
            ->postJson(route('video.answer', $room->room_token), [
                'sdp'            => ['type' => 'answer', 'sdp' => 'x'],
                'target_user_id' => $cita->cliente_id,
            ])
            ->assertStatus(403);
    }

    /** 14. Usuario ajeno NO puede enviar ICE. */
    public function test_usuario_ajeno_no_puede_enviar_ice(): void
    {
        [, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $ajeno = $this->makeUsuario('cliente', 'ajeno-cliente');
        $room  = $cita->videoRoom;

        $this->actingAs($ajeno)
            ->postJson(route('video.ice', $room->room_token), [
                'candidate'      => ['candidate' => 'candidate:1 1 udp 2130706431 1.2.3.4 5000 typ host', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
                'target_user_id' => $cita->abogado_id,
            ])
            ->assertStatus(403);
    }

    /**
     * Extra FASE 22: un participante NO puede enviar answer hacia alguien que
     * no sea el otro participante de su propia cita (destinatario inválido).
     */
    public function test_enviar_offer_a_peer_incorrecto_es_rechazado(): void
    {
        [$cliente, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        // El cliente sólo puede dirigirse a su abogado, no a sí mismo ni a un tercero.
        $this->actingAs($cliente)
            ->postJson(route('video.offer', $room->room_token), [
                'sdp'            => ['type' => 'offer', 'sdp' => 'x'],
                'target_user_id' => $cliente->id, // NO es el peer (es uno mismo)
            ])
            ->assertStatus(403);
    }

    /** 15. Valores de payload inválidos → 422 (validación request). */
    public function test_payload_invalido_devuelve_422(): void
    {
        [$cliente, , $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        // sdp.type incorrecto ('offer' donde se exige 'answer').
        $this->actingAs($cliente)
            ->postJson(route('video.answer', $room->room_token), [
                'sdp'            => ['type' => 'offer', 'sdp' => 'x'],
                'target_user_id' => $cita->abogado_id,
            ])
            ->assertStatus(422);

        // Falta el sdp completo.
        $this->actingAs($cliente)
            ->postJson(route('video.ice', $room->room_token), [
                'candidate'      => ['candidate' => ''],
                'target_user_id' => $cita->abogado_id,
            ])
            ->assertStatus(422);
    }

    // ─── FASE 23 — Misma sala para ambos roles ────────────────────

    /**
     * Una cita virtual confirmada → UNA VideoRoom.
     * Cliente y abogado abren la sala y ambos reciben la MISMA VideoRoom
     * (mismo id, mismo room_token). Sigue existiendo exactamente 1 sala.
     */
    public function test_cliente_y_abogado_abren_la_misma_sala(): void
    {
        [$cliente, $abogado, $cita] = $this->citaVirtualConfirmadaEnVentana();
        $room = $cita->videoRoom;

        $resCliente = $this->actingAs($cliente)->get(route('video.sala', $room->room_token));
        $resAbogado = $this->actingAs($abogado)->get(route('video.sala', $room->room_token));

        $resCliente->assertStatus(200);
        $resAbogado->assertStatus(200);

        // Exactamente 1 sala para esta cita.
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());

        // Ambos roles usan la MISMA sala (mismo id y mismo token).
        $misSalasCliente = VideoRoom::where('cita_id', $cita->id)->get();
        $this->assertCount(1, $misSalasCliente);
        $this->assertSame($room->id, $misSalasCliente->first()->id);
        $this->assertSame($room->room_token, $misSalasCliente->first()->room_token);
    }

    /** GET de sala jamás crea una nueva VideoRoom (aunque no exista). */
    public function test_get_de_sala_no_crea_video_room(): void
    {
        $cliente = $this->makeUsuario('cliente', 'cliente');
        $abogado = $this->makeUsuario('abogado', 'abogado');
        $ahora   = now();

        // Cita confirmada virtual, pero SIN cerrar creación de sala (se crea en confirmar).
        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => $ahora->toDateString(),
            'hora_inicio' => $ahora->format('H:i:s'),
            'hora_fin'    => $ahora->copy()->addHour()->format('H:i:s'),
            'tipo'        => 'consulta_general',
            'modalidad'   => 'virtual',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);
        app(CitaService::class)->confirmar($cita);
        $cita->refresh();

        $antes = VideoRoom::where('cita_id', $cita->id)->count();

        // Un usuario autorizado intenta abrir la sala con un token NO existente
        // de esa cita → 404 (no adivina token) y NO se crea ninguna sala nueva.
        $this->actingAs($cliente)
            ->get(route('video.sala', 'token-inventado'))
            ->assertStatus(404);

        $despues = VideoRoom::where('cita_id', $cita->id)->count();
        $this->assertSame($antes, $despues);
    }
}
