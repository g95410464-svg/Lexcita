<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoParticipant extends Model
{
    protected $table = 'video_participants';

    protected $fillable = [
        'room_id',
        'user_id',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
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
    public function estaConectado(): bool
    {
        return $this->joined_at !== null && $this->left_at === null;
    }

    public function registrarEntrada(): void
    {
        if (!$this->joined_at) {
            $this->update(['joined_at' => now()]);
        }
    }

    public function registrarSalida(): void
    {
        if ($this->estaConectado()) {
            $this->update(['left_at' => now()]);
        }
    }

    public function duracionSegundos(): ?int
    {
        if (!$this->joined_at) {
            return null;
        }
        $fin = $this->left_at ?? now();
        return $this->joined_at->diffInSeconds($fin);
    }

    // Helper para saber si es el cliente o abogado de la cita asociada
    public function esCliente(): bool
    {
        return $this->videoRoom && $this->videoRoom->cita
            && $this->user_id === $this->videoRoom->cita->cliente_id;
    }

    public function esAbogado(): bool
    {
        return $this->videoRoom && $this->videoRoom->cita
            && $this->user_id === $this->videoRoom->cita->abogado_id;
    }
}