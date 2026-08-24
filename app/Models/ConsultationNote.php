<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationNote extends Model
{
    protected $table = 'consultation_notes';

    protected $fillable = [
        'cita_id',
        'abogado_id',
        'notes',
    ];

    // ─── Relaciones ───────────────────────────────────────────
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function abogado()
    {
        return $this->belongsTo(Usuario::class, 'abogado_id');
    }

    // ─── Seguridad: nunca exponer al cliente ──────────────────
    /**
     * Determina si el usuario puede ver esta nota.
     * Solo el abogado que es parte de la cita asociada puede verla.
     */
    public function puedeVer(Usuario $usuario): bool
    {
        return $this->abogado_id === $usuario->id
            && $this->cita->abogado_id === $usuario->id;
    }

    // ─── Helper para vista ────────────────────────────────────
    public function preview(int $longitud = 120): string
    {
        return strlen($this->notes) > $longitud
            ? substr($this->notes, 0, $longitud) . '…'
            : $this->notes;
    }
}