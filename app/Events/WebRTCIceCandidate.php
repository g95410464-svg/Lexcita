<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario};

class WebRTCIceCandidate extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public array $candidate, // { candidate: '...', sdpMid: '...', sdpMLineIndex: 0 }
        public string $targetUserId
    ) {
        parent::__construct($room, $user, [
            'candidate' => $candidate,
            'target_user_id' => $targetUserId,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'webrtc.ice-candidate';
    }
}