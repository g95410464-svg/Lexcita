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
        'payment_status', 'paypal_order_id', 'transaction_id', 'paid_at',
    ];

    protected $casts = [
        'fecha'               => 'date',
        'monto'               => 'decimal:2',
        'cliente_consintio'   => 'boolean',
        'abogado_consintio'   => 'boolean',
        'paid_at'             => 'datetime',
    ];

    // ─── Helpers de pago ─────────────────────────────────────
    public function pagoCompletado(): bool
    {
        return $this->payment_status === 'completed';
    }

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

    // ─── Relación con VideoRoom ───────────────────────────────
    public function videoRoom()
    {
        return $this->hasOne(VideoRoom::class, 'cita_id');
    }

    public function notes()
    {
        return $this->hasMany(ConsultationNote::class, 'cita_id');
    }

    public function consultationNote()
    {
        return $this->hasOne(ConsultationNote::class, 'cita_id');
    }

    // ─── Helpers de videollamada ──────────────────────────────
    public function esVirtual(): bool
    {
        return $this->modalidad === 'virtual';
    }

    public function ventanaVideollamadaAbierta(): bool
    {
        if (!$this->esVirtual() || !$this->estaConfirmada()) {
            return false;
        }

        $inicio = Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_inicio);
        $fin    = Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_fin);
        $ahora  = now();

        // Ventana: 15 minutos antes de hora_inicio hasta hora_fin
        $ventanaInicio = $inicio->copy()->subMinutes(15);

        return $ahora->between($ventanaInicio, $fin);
    }

    public function puedeEntrarAVideollamada(Usuario $usuario): bool
    {
        if (!$this->ventanaVideollamadaAbierta()) {
            return false;
        }

        // Solo cliente o abogado de esta cita
        return $this->cliente_id === $usuario->id || $this->abogado_id === $usuario->id;
    }

    public function clienteHaConsentido(): bool
    {
        return $this->cliente_consintio ?? false;
    }

    public function abogadoHaConsentido(): bool
    {
        return $this->abogado_consintio ?? false;
    }

    public function ambosConsintieron(): bool
    {
        return $this->clienteHaConsentido() && $this->abogadoHaConsentido();
    }

    public function registrarConsentimientoCliente(): void
    {
        $this->update(['cliente_consintio' => true]);
    }

    public function registrarConsentimientoAbogado(): void
    {
        $this->update(['abogado_consintio' => true]);
    }
}