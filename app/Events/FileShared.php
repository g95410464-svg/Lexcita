<?php

namespace App\Events;

use App\Models\{VideoRoom, Usuario, VideoFile};

class FileShared extends VideoRoomEvent
{
    public function __construct(
        VideoRoom $room,
        Usuario $user,
        public VideoFile $file
    ) {
        parent::__construct($room, $user, [
            'file_id' => $file->id,
            'file_name' => $file->file_name,
            'mime_type' => $file->mime_type,
            'icono' => $file->iconoPorTipo(),
            'created_at' => $file->created_at?->toISOString(),
        ]);
    }

    public function broadcastAs(): string
    {
        return 'file.shared';
    }
}