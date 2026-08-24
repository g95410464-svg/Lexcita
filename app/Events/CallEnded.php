<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};

class CallEnded extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public bool $finalizadoPorAbogado = false,
        public ?int $duracionSegundos = null
    ) {
        parent::__construct($room, $user, [
            'finalizado_por_abogado' => $finalizadoPorAbogado,
            'duracion_segundos' => $duracionSegundos,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'call.ended';
    }
}