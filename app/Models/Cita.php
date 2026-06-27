<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'codigo', 'cliente_id', 'abogado_id',
        'fecha', 'hora_inicio', 'hora_fin',
        'tipo', 'modalidad', 'descripcion',
        'estado', 'monto',
        'stripe_session_id', 'stripe_payment_intent_id',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'monto'  => 'decimal:2',
    ];

    // ─── Relaciones ───────────────────────────────────────────
    public function cliente()  { return $this->belongsTo(Usuario::class, 'cliente_id'); }
    public function abogado()  { return $this->belongsTo(Usuario::class, 'abogado_id'); }

    // ─── Helpers de estado ────────────────────────────────────
    public function estaPendiente():  bool { return $this->estado === 'pendiente_pago'; }
    public function estaConfirmada(): bool { return $this->estado === 'confirmada'; }
    public function estaCancelada():  bool { return $this->estado === 'cancelada'; }

    public function puedeCancelarse(): bool
    {
        $inicio = Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_inicio);
        return $inicio->diffInHours(now()) > 24 && $this->estaConfirmada();
    }

    // ─── Generador de código ──────────────────────────────────
    public static function generarCodigo(): string
    {
        $ultimo = self::max('id') ?? 0;
        return 'LEX-' . date('Y') . '-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }

    // ─── Scope por estado ─────────────────────────────────────
    public function scopeConfirmadas($q) { return $q->where('estado', 'confirmada'); }
    public function scopePendientes($q)  { return $q->where('estado', 'pendiente_pago'); }
}
