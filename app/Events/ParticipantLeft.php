<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};

class ParticipantLeft extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public bool $fueFinalizadaPorAbogado = false
    ) {
        parent::__construct($room, $user, ['fue_finalizada_por_abogado' => $fueFinalizadaPorAbogado]);
    }

    public function broadcastAs(): string
    {
        return 'participant.left';
    }
}