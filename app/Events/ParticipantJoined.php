<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};

class ParticipantJoined extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public bool $esPrimero = false
    ) {
        parent::__construct($room, $user, ['es_primero' => $esPrimero]);
    }

    public function broadcastAs(): string
    {
        return 'participant.joined';
    }
}