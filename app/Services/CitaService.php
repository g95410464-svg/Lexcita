<?php

namespace App\Services;

use App\Models\{BookingHold, Cita, HorarioDisponible, Usuario};
use App\Exceptions\BookingSchemaNotReady;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CitaService
{
    // Excepción temporal de feria: reutiliza el horario activo del viernes.
    public const FECHA_FERIA = '2026-09-26';

    public function retencionesListas(): bool
    {
        return config('organization.id') === 'servicios-legales-integrados'
            && Schema::hasTable('booking_holds') && Schema::hasColumn('citas', 'hold_expires_at');
    }

    /**
     * Retorna slots disponibles para un abogado en una fecha dada.
     * Genera slots de 60 min dentro del horario disponible del día,
     * excluyendo los ya reservados.
     */
    public function getSlotsDisponibles(int $abogadoId, string $fecha): array
    {
        $this->cancelarReservasVencidas();

        $carbon   = Carbon::parse($fecha);
        $diaNombre = $carbon->toDateString() === self::FECHA_FERIA
            ? 'viernes'
            : $this->carbonDayToSpanish($carbon->dayOfWeekIso);

        if (!$diaNombre) {
            return []; // fin de semana
        }

        $horario = HorarioDisponible::where('abogado_id', $abogadoId)
            ->where('dia_semana', $diaNombre)
            ->where('activo', true)
            ->first();

        if (!$horario) {
            return [];
        }

        // Confirmadas y solicitudes de pago que todavía están dentro de sus
        // 15 minutos de retención bloquean el mismo horario.
        $holdsReady = $this->retencionesListas();
        $citasQuery = Cita::where('abogado_id', $abogadoId)->whereDate('fecha', $fecha);
        if ($holdsReady) {
            $citasQuery->where(function ($query): void {
                $query->where('estado', 'confirmada')
                    ->orWhere(function ($pending): void {
                        $pending->where('estado', 'pendiente_pago')
                            ->where(function ($payment): void {
                                $payment->where('payment_status', 'completed')
                                    ->orWhereNull('hold_expires_at')
                                    ->orWhere('hold_expires_at', '>', now());
                            });
                    });
            });
        } else {
            $citasQuery->where('estado', '!=', 'cancelada');
        }
        $reservadas = $citasQuery->pluck('hora_inicio')->toArray();

        $holds = $holdsReady
            ? BookingHold::where('abogado_id', $abogadoId)
                ->whereDate('fecha', $fecha)
                ->where('expires_at', '>', now())
                ->pluck('hora_inicio')->toArray()
            : [];
        $reservadas = array_merge($reservadas, $holds);

        // Generar slots de 60 min
        $slots    = [];
        $inicio   = Carbon::createFromTimeString($horario->hora_inicio);
        $fin      = Carbon::createFromTimeString($horario->hora_fin);
        $horaFin  = $fin->copy()->subHour(); // último slot empieza 1h antes del cierre

        $ahora = Carbon::now();

        while ($inicio <= $horaFin) {
            $hora = $inicio->format('H:i');
            $inicioSlot = $carbon->copy()->setTimeFromTimeString($hora . ':00');
            if ($inicioSlot->gt($ahora)
                && !in_array($hora . ':00', $reservadas)
                && !in_array($hora, $reservadas)) {
                $slots[] = [
                    'hora'       => $hora,
                    'hora_label' => $inicio->format('g:i A'),
                ];
            }
            $inicio->addHour();
        }

        return $slots;
    }

    /** Retiene un horario para el cliente actual durante 15 minutos. */
    public function retenerHorario(int $clienteId, int $abogadoId, string $fecha, string $hora): BookingHold
    {
        if (!$this->retencionesListas()) {
            throw new BookingSchemaNotReady();
        }

        return DB::transaction(function () use ($clienteId, $abogadoId, $fecha, $hora): BookingHold {
            Usuario::whereKey($abogadoId)
                ->where('rol', 'abogado')
                ->where('activo', true)
                ->lockForUpdate()
                ->firstOrFail();

            BookingHold::where('abogado_id', $abogadoId)
                ->whereDate('fecha', $fecha)
                ->where('hora_inicio', $hora)
                ->where('expires_at', '<=', now())
                ->delete();

            $slots = $this->getSlotsDisponibles($abogadoId, $fecha);
            if (!in_array($hora, array_column($slots, 'hora'), true)) {
                throw new \DomainException('Ese horario ya no está disponible. Elige otro.');
            }

            return BookingHold::create([
                'token' => (string) Str::uuid(),
                'cliente_id' => $clienteId,
                'abogado_id' => $abogadoId,
                'fecha' => $fecha,
                'hora_inicio' => $hora,
                'expires_at' => now()->addMinutes(15),
            ]);
        });
    }

    public function liberarRetencion(int $clienteId, string $token): void
    {
        if (!$this->retencionesListas()) {
            return;
        }
        BookingHold::where('cliente_id', $clienteId)->where('token', $token)->delete();
    }

    /** Marca como canceladas las citas SLI impagas cuyo plazo venció. */
    public function cancelarReservasVencidas(): int
    {
        if (!$this->retencionesListas()) {
            return 0;
        }

        BookingHold::where('expires_at', '<=', now())->delete();

        return Cita::where('estado', 'pendiente_pago')
            ->where(function ($query): void {
                $query->whereNull('payment_status')->orWhere('payment_status', '!=', 'completed');
            })
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->update(['estado' => 'cancelada', 'payment_status' => 'expired']);
    }

    /**
     * Confirma una cita y crea la sala de video si es virtual.
     * Punto único de confirmación (admin o cliente tras pago).
     *
     * Operación transaccional: la cita y su VideoRoom (si aplica)
     * se persisten de forma atómica. La creación de la sala se delega
     * en VideoRoomService para mantener la lógica desacoplada.
     */
    public function confirmar(Cita $cita): Cita
    {
        $this->cancelarReservasVencidas();

        // Solo se puede confirmar si está pendiente de pago
        if (!$cita->estaPendiente()) {
            throw new \InvalidArgumentException('Solo se pueden confirmar citas en estado "pendiente_pago".');
        }

        return DB::transaction(function () use ($cita) {
            if ($this->retencionesListas()) {
                Usuario::whereKey($cita->abogado_id)->lockForUpdate()->firstOrFail();
                $cita = Cita::whereKey($cita->id)->lockForUpdate()->firstOrFail();

                if (!$cita->estaPendiente()) {
                    throw new \InvalidArgumentException('Solo se pueden confirmar citas pendientes.');
                }

                if ($cita->hold_expires_at && $cita->hold_expires_at->lte(now()) && !$cita->pagoCompletado()) {
                    $cita->update(['estado' => 'cancelada', 'payment_status' => 'expired']);
                    throw new \InvalidArgumentException('El plazo de pago venció y el horario fue liberado.');
                }

                $conflictoCita = Cita::where('abogado_id', $cita->abogado_id)
                    ->whereDate('fecha', $cita->fecha->toDateString())
                    ->where('hora_inicio', $cita->hora_inicio)
                    ->where('id', '!=', $cita->id)
                    ->where(function ($query): void {
                        $query->where('estado', 'confirmada')
                            ->orWhere(function ($pending): void {
                                $pending->where('estado', 'pendiente_pago')
                                    ->where(function ($payment): void {
                                        $payment->where('payment_status', 'completed')
                                            ->orWhereNull('hold_expires_at')
                                            ->orWhere('hold_expires_at', '>', now());
                                    });
                            });
                    })
                    ->exists();
                $conflictoHold = BookingHold::where('abogado_id', $cita->abogado_id)
                    ->whereDate('fecha', $cita->fecha->toDateString())
                    ->where('hora_inicio', $cita->hora_inicio)
                    ->where('expires_at', '>', now())
                    ->exists();

                if ($conflictoCita || $conflictoHold) {
                    throw new \InvalidArgumentException('Ese horario ya está apartado por otra reserva.');
                }
            }

            $cita->update(['estado' => 'confirmada']);

            // Si es virtual, delegar la creación de la sala al VideoRoomService
            if ($cita->esVirtual()) {
                app(VideoRoomService::class)->obtenerOCrearSala($cita);
            }

            return $cita->fresh();
        });
    }

    private function carbonDayToSpanish(int $iso): ?string
    {
        return match($iso) {
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            default => null,
        };
    }
}
