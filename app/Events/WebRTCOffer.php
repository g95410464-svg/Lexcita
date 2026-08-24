<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};

class WebRTCOffer extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public array $sdp, // { type: 'offer', sdp: '...' }
        public string $targetUserId // user_id del destinatario
    ) {
        parent::__construct($room, $user, [
            'sdp' => $sdp,
            'target_user_id' => $targetUserId,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'webrtc.offer';
    }
}