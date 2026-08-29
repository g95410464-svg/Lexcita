<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoRoom extends Model
{
    protected $table = 'video_rooms';

    protected $fillable = [
        'cita_id',
        'room_token',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function participantes()
    {
        return $this->hasMany(VideoParticipant::class, 'room_id');
    }

    public function mensajes()
    {
        return $this->hasMany(VideoMessage::class, 'room_id')->orderBy('created_at');
    }

    public function archivos()
    {
        return $this->hasMany(VideoFile::class, 'room_id')->orderBy('created_at');
    }

    // ─── Helpers ──────────────────────────────────────────────
    public function estaProgramada(): bool
    {
        return $this->status === 'programada';
    }

    public function estaDisponible(): bool
    {
        return $this->status === 'disponible';
    }

    public function estaEnEspera(): bool
    {
        return $this->status === 'en_espera';
    }

    public function estaEnConsulta(): bool
    {
        return $this->status === 'en_consulta';
    }

    public function estaFinalizada(): bool
    {
        return $this->status === 'finalizada';
    }

    public function marcarDisponible(): void
    {
        $this->update(['status' => 'disponible']);
    }

    public function marcarEnEspera(): void
    {
        $this->update(['status' => 'en_espera']);
    }

    public function marcarEnConsulta(): void
    {
        $this->update([
            'status'      => 'en_consulta',
            'started_at'  => now(),
        ]);
    }

    public function marcarFinalizada(): void
    {
        $this->update([
            'status'    => 'finalizada',
            'ended_at'  => now(),
        ]);
    }

    public function duracionSegundos(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->ended_at);
    }

    // Scope para consultas frecuentes
    public function scopeActivas($query)
    {
        return $query->whereIn('status', ['disponible', 'en_espera', 'en_consulta']);
    }
}