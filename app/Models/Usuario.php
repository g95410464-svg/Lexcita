<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
        'telefono_whatsapp',
        'especialidad',
        'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'activo'   => 'boolean',
        'password' => 'hashed',
    ];

    // ─── Helpers de rol ───────────────────────────────────────
    public function esCliente():  bool { return $this->rol === 'cliente'; }
    public function esAbogado():  bool { return $this->rol === 'abogado'; }
    public function esAdmin():    bool { return $this->rol === 'admin'; }

    // ─── Relaciones ───────────────────────────────────────────
    public function citasComoCliente()
    {
        return $this->hasMany(Cita::class, 'cliente_id');
    }

    public function citasComoAbogado()
    {
        return $this->hasMany(Cita::class, 'abogado_id');
    }

    public function horarios()
    {
        return $this->hasMany(HorarioDisponible::class, 'abogado_id');
    }

    // ─── Relaciones de videollamada ────────────────────────────
    public function videoRoomsComoCliente()
    {
        return $this->hasManyThrough(VideoRoom::class, Cita::class, 'cliente_id', 'cita_id');
    }

    public function videoRoomsComoAbogado()
    {
        return $this->hasManyThrough(VideoRoom::class, Cita::class, 'abogado_id', 'cita_id');
    }

    public function videoParticipants()
    {
        return $this->hasMany(VideoParticipant::class, 'user_id');
    }

    public function videoMessages()
    {
        return $this->hasMany(VideoMessage::class, 'user_id');
    }

    public function videoFiles()
    {
        return $this->hasMany(VideoFile::class, 'user_id');
    }

    public function consultationNotes()
    {
        return $this->hasMany(ConsultationNote::class, 'abogado_id');
    }

    // ─── Scope: solo abogados activos ─────────────────────────
    public function scopeAbogadosActivos($query)
    {
        return $query->where('rol', 'abogado')->where('activo', true);
    }
}