<?php

namespace App\Services\Agenda;

use App\Models\{AgendaEvent, AgendaLocation, BookingHold, Cita, HorarioDisponible, Usuario};
use App\Support\AgendaModule;
use App\Services\CitaService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    public function slots(int $lawyerId, string $date, ?int $ignoreAppointment = null, ?int $ignoreHold = null): array
    {
        $context = $this->context($lawyerId, $date);
        if (!$context['lawyer_active'] || !$context['hours']) return [];
        $slots = [];
        foreach ($context['hours'] as $hours) {
            $start = $this->businessTime($date, $hours->hora_inicio);
            $end = $this->businessTime($date, $hours->hora_fin);
            for ($cursor = $start; $cursor->addHour()->lte($end); $cursor = $cursor->addHour()) {
                if ($cursor->gt(now()) && !$this->issues($context, $cursor, $cursor->addHour(), $ignoreAppointment, $ignoreHold)) {
                    $slots[$cursor->format('H:i')] = ['hora' => $cursor->format('H:i'), 'hora_label' => $cursor->format('g:i A')];
                }
            }
        }
        ksort($slots);
        return array_values($slots);
    }

    public function occupyingAppointments(int $lawyerId)
    {
        $query = Cita::where('abogado_id', $lawyerId);
        if (app(CitaService::class)->retencionesListas()) {
            return $query->where(function ($q): void {
                $q->where('estado', 'confirmada')->orWhere(function ($p): void {
                    $p->where('estado', 'pendiente_pago')->where(function ($pay): void {
                        $pay->where('payment_status', 'completed')->orWhereNull('hold_expires_at')
                            ->orWhere('hold_expires_at', '>', now());
                    });
                });
            });
        }
        return $query->where('estado', '!=', 'cancelada');
    }

    public function context(int $lawyerId, string $date): array
    {
        $day = CarbonImmutable::parse($date, config('agenda.timezone'))->startOfDay();
        $dayName = $date === CitaService::FECHA_FERIA ? 'viernes' :
            ([1=>'lunes',2=>'martes',3=>'miercoles',4=>'jueves',5=>'viernes'][$day->dayOfWeekIso] ?? null);
        $hours = $dayName ? HorarioDisponible::where('abogado_id', $lawyerId)->where('dia_semana', $dayName)
            ->where('activo', true)->orderBy('hora_inicio')->get() : collect();
        $appointments = $this->occupyingAppointments($lawyerId)
            ->whereBetween('fecha', [$day->subDay()->toDateString(), $day->addDay()->toDateString()])->get();
        $holds = app(CitaService::class)->retencionesListas() ? BookingHold::where('abogado_id', $lawyerId)
            ->whereBetween('fecha', [$day->subDay()->toDateString(), $day->addDay()->toDateString()])
            ->where('expires_at', '>', now())->get() : collect();
        $smart = app(AgendaModule::class)->ready();
        $events = $smart ? AgendaEvent::where('abogado_id', $lawyerId)->whereNull('cancelled_at')->where('blocking', true)
            ->where('starts_at', '<', $day->addDays(2)->utc()->format('Y-m-d H:i:s'))
            ->where('ends_at', '>', $day->subDay()->utc()->format('Y-m-d H:i:s'))->get() : collect();
        $settings = $smart ? DB::table('agenda_settings')->where('abogado_id', $lawyerId)->first() : null;
        $locations = $smart ? AgendaLocation::where('abogado_id', $lawyerId)->where('active', true)->get()->keyBy('id') : collect();
        return ['lawyer_active' => Usuario::whereKey($lawyerId)->where('rol', 'abogado')->where('activo', true)->exists(),
            'hours' => $hours, 'appointments' => $appointments, 'holds' => $holds,
            'smart' => $smart, 'events' => $events, 'settings' => $settings, 'locations' => $locations, 'date' => $date];
    }

    /** Internal reasons only: never return these or the private timeline in /api/slots. */
    public function issues(array $context, CarbonImmutable $start, CarbonImmutable $end,
        ?int $ignoreAppointment = null, ?int $ignoreHold = null, ?int $slotLocation = null): array
    {
        $timeline = [];
        $settings = $context['settings'];
        $base = $settings?->base_location_id;
        $slotLocation ??= $base;
        foreach ($context['appointments'] as $cita) {
            if ($cita->id === $ignoreAppointment) continue;
            $from = $this->businessTime($cita->fecha->toDateString(), $cita->hora_inicio);
            $to = $this->businessTime($cita->fecha->toDateString(), $cita->hora_fin);
            if ($to->lte($from)) $to = $to->addDay();
            $timeline[] = ['key'=>'appointment:'.$cita->id, 'start'=>$from, 'end'=>$to,
                'location'=>$cita->agenda_location_id ?? $base, 'margin'=>0, 'event_id'=>null,
                'physical'=>$cita->modalidad === 'presencial', 'mode'=>null];
        }
        foreach ($context['holds'] as $hold) {
            if ($hold->id === $ignoreHold) continue;
            $from = $this->businessTime($hold->fecha->toDateString(), $hold->hora_inicio);
            $timeline[] = ['key'=>'hold:'.$hold->id, 'start'=>$from, 'end'=>$from->addHour(),
                'location'=>$base, 'margin'=>0, 'event_id'=>null, 'physical'=>false, 'mode'=>null];
        }
        foreach ($context['events'] as $event) {
            $timeline[] = ['key'=>'event:'.$event->id, 'start'=>$event->start(), 'end'=>$event->end(),
                'location'=>$event->location_id ?? ($event->modality === 'virtual' ? $base : null),
                'margin'=>$event->margin_minutes, 'event_id'=>$event->id, 'physical'=>$event->modality === 'presencial', 'mode'=>$event->transport_mode];
        }
        $issues = [];
        foreach ($timeline as $item) {
            if ($item['start']->lt($end) && $item['end']->gt($start)) {
                $issues[$item['key']] = ['reason'=>'Superposición con un compromiso', 'event_id'=>$item['event_id']];
            }
        }
        if ($issues || !$context['smart']) return $issues;

        // Explicit start/end locations create physical boundaries for this workday.
        if ($settings && $context['hours']->isNotEmpty()) {
            $first = $this->businessTime($context['date'], $context['hours']->first()->hora_inicio);
            $last = $this->businessTime($context['date'], $context['hours']->last()->hora_fin);
            foreach ([['id'=>$settings->day_start_location_id,'time'=>$first,'key'=>'day-start'],
                ['id'=>$settings->day_end_location_id,'time'=>$last,'key'=>'day-end']] as $boundary) {
                if ($boundary['id']) $timeline[] = ['key'=>$boundary['key'],'start'=>$boundary['time'],
                    'end'=>$boundary['time'],'location'=>$boundary['id'],'margin'=>0,'event_id'=>null,'physical'=>true,'mode'=>null];
            }
        }
        $previous = collect($timeline)->filter(fn ($t) => $t['end']->lte($start))->sortByDesc(fn ($t) => $t['end']->timestamp)->first();
        $next = collect($timeline)->filter(fn ($t) => $t['start']->gte($end))->sortBy(fn ($t) => $t['start']->timestamp)->first();
        foreach ([['item'=>$previous,'before'=>true], ['item'=>$next,'before'=>false]] as $leg) {
            $item = $leg['item'];
            if (!$item) continue;
            // Unknown virtual locations share the configured workspace, not an
            // invented trip. A physical commitment without coordinates is uncertain.
            if ($item['location'] === null && $slotLocation === null && !$item['physical']) {
                $minutes = (int) ($settings?->virtual_buffer_minutes ?? 0);
            } else {
                $origin = $context['locations']->get($leg['before'] ? $item['location'] : $slotLocation);
                $destination = $context['locations']->get($leg['before'] ? $slotLocation : $item['location']);
                $departure = $leg['before'] ? $item['end'] : $end;
                $estimate = app(TravelTimeService::class)->estimate($origin, $destination,
                    $item['mode'] ?? $settings?->transport_mode ?? 'driving-car', $departure);
                if (!$estimate) {
                    $issues['travel:'.$item['key']] = ['reason'=>'Trayecto pendiente de estimación fiable', 'event_id'=>$item['event_id']];
                    continue;
                }
                $minutes = $estimate->minutes() + ($estimate->seconds > 0
                    ? max((int) ($settings?->margin_minutes ?? config('agenda.margin_minutes')), (int) $item['margin'])
                    : (int) ($settings?->virtual_buffer_minutes ?? 0));
            }
            $fits = $leg['before'] ? $item['end']->addMinutes($minutes)->lte($start)
                : $end->addMinutes($minutes)->lte($item['start']);
            if (!$fits) $issues['travel:'.$item['key']] = ['reason'=>'Tiempo insuficiente para el siguiente trayecto', 'event_id'=>$item['event_id']];
        }
        return $issues;
    }

    public function businessTime(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date.' '.substr($time, 0, 8), config('agenda.timezone'));
    }
}
