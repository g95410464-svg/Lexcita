<?php

namespace App\Services;

use App\Models\{Cita, HorarioDisponible, VideoRoom, VideoParticipant};
use Carbon\Carbon;
use Illuminate\Support\Str;

class CitaService
{
    /**
     * Retorna slots disponibles para un abogado en una fecha dada.
     * Genera slots de 60 min dentro del horario disponible del día,
     * excluyendo los ya reservados.
     */
    public function getSlotsDisponibles(int $abogadoId, string $fecha): array
    {
        $carbon   = Carbon::parse($fecha);
        $diaNombre = $this->carbonDayToSpanish($carbon->dayOfWeekIso); // 1=lun .. 5=vie

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

        // Citas ya confirmadas ese día
        $reservadas = Cita::where('abogado_id', $abogadoId)
            ->where('fecha', $fecha)
            ->where('estado', '!=', 'cancelada')
            ->pluck('hora_inicio')
            ->toArray();

        // Generar slots de 60 min
        $slots    = [];
        $inicio   = Carbon::createFromFormat('H:i:s', $horario->hora_inicio);
        $fin      = Carbon::createFromFormat('H:i:s', $horario->hora_fin);
        $horaFin  = $fin->copy()->subHour(); // último slot empieza 1h antes del cierre

        while ($inicio <= $horaFin) {
            $hora = $inicio->format('H:i');
            if (!in_array($hora . ':00', $reservadas) && !in_array($hora, $reservadas)) {
                $slots[] = [
                    'hora'       => $hora,
                    'hora_label' => $inicio->format('g:i A'),
                ];
            }
            $inicio->addHour();
        }

        return $slots;
    }

    /**
     * Confirma una cita y crea la sala de video si es virtual.
     * Punto único de confirmación (admin o cliente tras pago).
     */
    public function confirmar(Cita $cita): Cita
    {
        // Solo se puede confirmar si está pendiente de pago
        if (!$cita->estaPendiente()) {
            throw new \InvalidArgumentException('Solo se pueden confirmar citas en estado "pendiente_pago".');
        }

        $cita->update(['estado' => 'confirmada']);

        // Si es virtual, crear VideoRoom automáticamente
        if ($cita->esVirtual()) {
            $this->crearVideoRoom($cita);
        }

        return $cita->fresh();
    }

    /**
     * Crea la sala de video para una cita virtual confirmada.
     */
    private function crearVideoRoom(Cita $cita): VideoRoom
    {
        // Verificar si ya existe (idempotencia)
        if ($cita->videoRoom) {
            return $cita->videoRoom;
        }

        $roomToken = Str::random(40); // token no adivinable

        $videoRoom = VideoRoom::create([
            'cita_id'    => $cita->id,
            'room_token' => $roomToken,
            'status'     => 'programada',
        ]);

        // Pre-registrar a ambos participantes (entrarán cuando den consentimiento)
        VideoParticipant::create([
            'room_id' => $videoRoom->id,
            'user_id' => $cita->cliente_id,
        ]);

        VideoParticipant::create([
            'room_id' => $videoRoom->id,
            'user_id' => $cita->abogado_id,
        ]);

        return $videoRoom;
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
