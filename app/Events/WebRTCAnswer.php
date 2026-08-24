<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};

class WebRTCAnswer extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public array $sdp, // { type: 'answer', sdp: '...' }
        public string $targetUserId
    ) {
        parent::__construct($room, $user, [
            'sdp' => $sdp,
            'target_user_id' => $targetUserId,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'webrtc.answer';
    }
}