<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoFile extends Model
{
    protected $table = 'video_files';

    protected $fillable = [
        'room_id',
        'user_id',
        'file_name',
        'file_path',
        'mime_type',
        'tamano',
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

    // ─── Helpers ──────────────────────────────────────────────
    public function getUrlDescarga(): string
    {
        return route('video-room.archivo.descargar', ['room_token' => $this->videoRoom->room_token ?? '', 'file' => $this->id]);
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function esPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function iconoPorTipo(): string
    {
        if ($this->esImagen()) return 'image';
        if ($this->esPdf()) return 'picture_as_pdf';
        return 'insert_drive_file';
    }
}