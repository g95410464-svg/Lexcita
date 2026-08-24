<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoMessage extends Model
{
    protected $table = 'video_messages';

    protected $fillable = [
        'room_id',
        'user_id',
        'message',
    ];

    // ─── Relaciones ───────────────────────────────────────────
    public function videoRoom()
    {
        return $this->belongsTo(VideoRoom::class, 'room_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // ─── Helper para vista ────────────────────────────────────
    public function esDelUsuarioActual(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}