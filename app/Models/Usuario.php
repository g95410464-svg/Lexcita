<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Usuario extends Authenticatable implements MustVerifyEmail
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
        'activo'            => 'boolean',
        'password'          => 'hashed',
        'email_verified_at' => 'datetime',
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

    // ─── Scope: solo abogados activos ─────────────────────────
    public function scopeAbogadosActivos($query)
    {
        return $query->where('rol', 'abogado')->where('activo', true);
    }
}