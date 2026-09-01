<?php

namespace App\Http\Controllers;

use App\Events\{ParticipantJoined, ParticipantLeft, WebRTCAnswer, WebRTCOffer, WebRTCIceCandidate};
use App\Models\{VideoRoom, Usuario};
use App\Services\VideoRoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sala de videollamada WebRTC.
 *
 * Ruta única compartida (cliente y abogado de la MISMA cita):
 *   GET  /videollamada/{roomToken}   → video.sala
 *   POST /videollamada/{roomToken}/offer|answer|ice|leave
 *
 * La autorización SIEMPRE es server-side (validarAcceso + pertenencia a la cita).
 * GET nunca crea una sala (solo la muestra si ya existe por token).
 * Los endpoints de signaling reenvían el payload a los demás suscriptores del
 * canal privado mediante eventos ShouldBroadcast.
 */
class VideoRoomController extends Controller
{
    protected function salaPorToken(string $roomToken): VideoRoom
    {
        return VideoRoom::where('room_token', $roomToken)
            ->with(['cita.cliente', 'cita.abogado', 'participantes.usuario'])
            ->firstOrFail();
    }

    /**
     * Devuelve el otro participante de la cita (el "peer" para WebRTC).
     */
    protected function peerDe(VideoRoom $room, Usuario $user): Usuario
    {
        $cita = $room->cita;
        return $cita->cliente_id === $user->id ? $cita->abogado : $cita->cliente;
    }

    /**
     * GET /videollamada/{roomToken}
     * Muestra la sala. Valida acceso (pertenencia + confirmada + virtual +
     * ventana abierta), registra la entrada y emite participant.joined.
     * NUNCA crea una sala: solo la localiza por token (o 404).
     */
    public function show(string $roomToken)
    {
        $room = $this->salaPorToken($roomToken);
        $user = Auth::user();

        [$permitido, $mensaje] = app(VideoRoomService::class)->validarAcceso($room, $user);
        if (!$permitido) {
            abort(403, $mensaje);
        }

        $service = app(VideoRoomService::class);

        // ¿Soy el primero en llegar? (antes de registrar esta entrada)
        $otrosConectado = $room->participantes()
            ->where('user_id', '!=', $user->id)
            ->whereNull('left_at')
            ->whereNotNull('joined_at')
            ->exists();
        $esPrimero = !$otrosConectado;

        $service->registrarEntrada($room, $user);

        $room->refresh();
        $room->load(['cita', 'participantes.usuario']);

        event(new ParticipantJoined($room, $user, $esPrimero));

        $cita       = $room->cita;
        $esAbogado  = $cita->abogado_id === $user->id;
        $peer       = $this->peerDe($room, $user);
        $stun       = app(VideoRoomService::class)->getStunConfig();

        return view('video.sala', compact('room', 'cita', 'user', 'esAbogado', 'peer', 'esPrimero', 'stun'));
    }

    /**
     * Valida acceso (o aborta 403) y devuelve la sala, para signaling.
     */
    protected function salaAccesible(string $roomToken): VideoRoom
    {
        $room = $this->salaPorToken($roomToken);
        $user = Auth::user();
        [$permitido, $mensaje] = app(VideoRoomService::class)->validarAcceso($room, $user);
        if (!$permitido) {
            abort(403, $mensaje);
        }
        return $room;
    }

    /**
     * Comprueba que target_user_id sea exactamente el otro participante de la cita.
     */
    protected function esPeerValido(VideoRoom $room, Usuario $user, $targetUserId): bool
    {
        $cita = $room->cita;
        $peerId = $cita->cliente_id === $user->id ? $cita->abogado_id : $cita->cliente_id;
        return (int) $targetUserId === (int) $peerId;
    }

    /** POST /videollamada/{roomToken}/offer */
    public function offer(Request $request, string $roomToken)
    {
        $room  = $this->salaAccesible($roomToken);
        $user  = Auth::user();

        $data = $request->validate([
            'sdp'                => 'required|array',
            'sdp.type'           => 'required|string|in:offer',
            'sdp.sdp'            => 'required|string',
            'target_user_id'     => 'required|integer',
        ]);

        if (!$this->esPeerValido($room, $user, $data['target_user_id'])) {
            abort(403, 'Destinatario inválido.');
        }

        event(new WebRTCOffer($room, $user, $data['sdp'], (string) $data['target_user_id']));

        return response()->json(['success' => true]);
    }

    /** POST /videollamada/{roomToken}/answer */
    public function answer(Request $request, string $roomToken)
    {
        $room  = $this->salaAccesible($roomToken);
        $user  = Auth::user();

        $data = $request->validate([
            'sdp'                => 'required|array',
            'sdp.type'           => 'required|string|in:answer',
            'sdp.sdp'            => 'required|string',
            'target_user_id'     => 'required|integer',
        ]);

        if (!$this->esPeerValido($room, $user, $data['target_user_id'])) {
            abort(403, 'Destinatario inválido.');
        }

        event(new WebRTCAnswer($room, $user, $data['sdp'], (string) $data['target_user_id']));

        return response()->json(['success' => true]);
    }

    /** POST /videollamada/{roomToken}/ice */
    public function ice(Request $request, string $roomToken)
    {
        $room  = $this->salaAccesible($roomToken);
        $user  = Auth::user();

        $data = $request->validate([
            'candidate'            => 'required|array',
            'candidate.candidate'  => 'required|string',
            'candidate.sdpMid'     => 'nullable|string',
            'candidate.sdpMLineIndex' => 'nullable|integer',
            'target_user_id'       => 'required|integer',
        ]);

        if (!$this->esPeerValido($room, $user, $data['target_user_id'])) {
            abort(403, 'Destinatario inválido.');
        }

        event(new WebRTCIceCandidate($room, $user, $data['candidate'], (string) $data['target_user_id']));

        return response()->json(['success' => true]);
    }

    /**
     * POST /videollamada/{roomToken}/leave
     * Avisa a los demás que este participante colgó. NO registra salida en BD
     * (para permitir recargar la página sin quedar desconectado de forma
     * permanente: registrarSalida es terminal en VideoRoomService).
     */
    public function leave(Request $request, string $roomToken)
    {
        $room = $this->salaAccesible($roomToken);
        $user = Auth::user();

        event(new ParticipantLeft($room, $user));

        return response()->json(['success' => true]);
    }
}
