<?php

use App\Models\{Cita, VideoRoom, Usuario};
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Canal privado para sala de videollamada.
 * Solo el cliente y el abogado de la cita pueden acceder.
 * Validación por room_token (no adivinable) + user_id pertenencia a la cita.
 *
 * IMPORTANTE: el patrón se registra SIN prefijos 'private-'/'presence-'.
 * Laravel recibe 'private-video-room.{token}', le quita el prefijo y lo
 * compara contra este patrón. Registrarlo con el prefijo rompía el match
 * y devolvía 403 para TODOS los usuarios (Application/channel no autorizada).
 */
Broadcast::channel('video-room.{room_token}', function (Usuario $user, string $room_token) {
    // Buscar la sala por token
    $room = VideoRoom::where('room_token', $room_token)
        ->with('cita')
        ->first();

    if (!$room) {
        return false;
    }

    $cita = $room->cita;

    // Validar: usuario es cliente o abogado de ESTA cita
    $esParticipante = $cita->cliente_id === $user->id || $cita->abogado_id === $user->id;

    // Validar: cita confirmada y virtual
    $citaValida = $cita->estaConfirmada() && $cita->esVirtual();

    // Validar: dentro de la ventana de tiempo
    $ventanaAbierta = $cita->ventanaVideollamadaAbierta();

    return $esParticipante && $citaValida && $ventanaAbierta;
});

/**
 * Canal de presencia para saber quién está en la sala (opcional, para futuro).
 */
Broadcast::channel('presence-video-room.{room_token}', function (Usuario $user, string $room_token) {
    $room = VideoRoom::where('room_token', $room_token)->with('cita')->first();

    if (!$room) {
        return false;
    }

    $cita = $room->cita;

    if ($cita->cliente_id !== $user->id && $cita->abogado_id !== $user->id) {
        return false;
    }

    if (!$cita->estaConfirmada() || !$cita->esVirtual() || !$cita->ventanaVideollamadaAbierta()) {
        return false;
    }

    // Retornar datos del usuario para presencia
    return [
        'id' => $user->id,
        'nombre' => $user->nombre,
        'rol' => $user->rol,
        'avatar_inicial' => mb_substr($user->nombre, 0, 1),
    ];
});