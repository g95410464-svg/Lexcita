<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario, VideoMessage};

class ChatMessage extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public VideoMessage $message
    ) {
        parent::__construct($room, $user, [
            'message_id' => $message->id,
            'content' => $message->message,
            'created_at' => $message->created_at?->toISOString(),
        ]);
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }
}