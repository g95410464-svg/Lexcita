<?php

namespace App\Services\Agenda;

use App\Models\{AgendaConflict, AgendaEvent, AgendaLocation};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgendaService
{
    public function saveEvent(int $lawyer, array $data, ?AgendaEvent $event = null): AgendaEvent
    {
        return app(CalendarLock::class)->run($lawyer, function () use ($lawyer, $data, $event) {
            if ($event) $event = AgendaEvent::where('abogado_id',$lawyer)->whereNull('cancelled_at')->lockForUpdate()->findOrFail($event->id);
            $location = empty($data['location_id']) ? null : AgendaLocation::where('abogado_id', $lawyer)
                ->where('active', true)->findOrFail($data['location_id']);
            if ($data['modality'] === 'presencial' && !$location) {
                throw ValidationException::withMessages(['location_id'=>'Elige una ubicación con coordenadas para la actividad presencial.']);
            }
            $start = CarbonImmutable::parse($data['starts_at'], config('agenda.timezone'));
            $end = CarbonImmutable::parse($data['ends_at'], config('agenda.timezone'));
            if (!$start->lt($end) || $end->gt($start->addDays(14))) {
                throw ValidationException::withMessages(['ends_at'=>'El fin debe ser posterior al inicio y no superar catorce días.']);
            }
            $before = $event?->only(['title','starts_at','ends_at','location_id','blocking']);
            $weeks = $event ? 1 : (int) ($data['repeat_weeks'] ?? 1);
            $series = $weeks > 1 ? (string) Str::uuid() : null;
            $saved = [];
            for ($week = 0; $week < $weeks; $week++) {
                $item = $week === 0 && $event ? $event : new AgendaEvent();
                $item->fill([
                    'abogado_id'=>$lawyer, 'title'=>$data['title'], 'type'=>$data['type'],
                    'starts_at'=>$start->addWeeks($week)->utc()->format('Y-m-d H:i:s'),
                    'ends_at'=>$end->addWeeks($week)->utc()->format('Y-m-d H:i:s'),
                    'location_id'=>$location?->id, 'modality'=>$data['modality'],
                    'blocking'=>(bool) ($data['blocking'] ?? false), 'private_note'=>$data['private_note'] ?? null,
                    'transport_mode'=>$data['transport_mode'], 'margin_minutes'=>$data['margin_minutes'],
                ]);
                if ($series) $item->series_id = $series;
                $item->save();
                $saved[] = $item;
                app(AgendaJournal::class)->audit($lawyer, $lawyer, $before ? 'event.updated' : 'event.created', 'event',
                    $item->id, $before, $item->only(['title','starts_at','ends_at','location_id','blocking']));
            }
            app(AgendaConflictService::class)->reconcile($lawyer);
            $affected = AgendaConflict::whereIn('event_id', collect($saved)->pluck('id'))
                ->whereIn('status', ['abierto','en_negociacion','escalado'])->distinct()->count('cita_id');
            if ($affected && empty($data['acknowledge_conflicts'])) {
                // Roll back activity, audits, alerts and conflicts together.
                throw ValidationException::withMessages(['acknowledge_conflicts'=>
                    "Esta actividad afecta {$affected} cita(s). Confirma que deseas guardarla; las citas conservarán su horario hasta que el cliente acepte un cambio."]);
            }
            return $saved[0];
        });
    }

    public function cancelEvent(int $lawyer, AgendaEvent $event): void
    {
        app(CalendarLock::class)->run($lawyer, function () use ($lawyer, $event): void {
            $event = AgendaEvent::where('abogado_id',$lawyer)->lockForUpdate()->findOrFail($event->id);
            $event->update(['cancelled_at'=>now()]);
            app(AgendaJournal::class)->audit($lawyer, $lawyer, 'event.cancelled', 'event', $event->id, null, null);
            app(AgendaConflictService::class)->reconcile($lawyer);
        });
    }

    public function saveLocation(int $lawyer, array $data, ?AgendaLocation $location = null): AgendaLocation
    {
        return app(CalendarLock::class)->run($lawyer, function () use ($lawyer, $data, $location) {
            if ($location && $location->exists) {
                $location = AgendaLocation::where('abogado_id',$lawyer)->lockForUpdate()->findOrFail($location->id);
                $used = \App\Models\Cita::where('abogado_id',$lawyer)->where('agenda_location_id',$location->id)
                    ->where('estado','confirmada')->where('fecha','>=',today()->toDateString())->exists();
                if ($used && ((float)$location->latitude !== (float)$data['latitude'] || (float)$location->longitude !== (float)$data['longitude'] || empty($data['active'] ?? true))) {
                    throw ValidationException::withMessages(['location'=>'Esta ubicación pertenece a citas confirmadas. Crea otra ubicación para cambiar sus coordenadas.']);
                }
            } else $location = null;
            $location ??= new AgendaLocation();
            $location->fill(['abogado_id'=>$lawyer, ...$data])->save();
            $location->refresh();
            // A previous manual estimate is not valid after changing its coordinates.
            DB::table('agenda_manual_routes')->where('abogado_id', $lawyer)
                ->where(fn ($q) => $q->where('origin_id', $location->id)->orWhere('destination_id', $location->id))->delete();
            app(AgendaJournal::class)->audit($lawyer, $lawyer, 'location.saved', 'location', $location->id, null, ['active'=>$location->active]);
            app(AgendaConflictService::class)->reconcile($lawyer);
            return $location;
        });
    }

    public function saveSettings(int $lawyer, array $data): void
    {
        app(CalendarLock::class)->run($lawyer, function () use ($lawyer, $data): void {
            foreach (['base_location_id','day_start_location_id','day_end_location_id'] as $key) {
                if (!empty($data[$key])) AgendaLocation::where('abogado_id', $lawyer)->where('active', true)->findOrFail($data[$key]);
            }
            DB::table('agenda_settings')->updateOrInsert(['abogado_id'=>$lawyer], [...$data, 'updated_at'=>now()]);
            app(AgendaJournal::class)->audit($lawyer, $lawyer, 'settings.saved', 'lawyer', $lawyer, null, $data);
            app(AgendaConflictService::class)->reconcile($lawyer);
        });
    }

    public function saveManualRoute(int $lawyer, array $data): void
    {
        app(CalendarLock::class)->run($lawyer, function () use ($lawyer, $data): void {
            foreach (['origin_id','destination_id'] as $key) {
                AgendaLocation::where('abogado_id', $lawyer)->where('active', true)->findOrFail($data[$key]);
            }
            $pairs = [[$data['origin_id'],$data['destination_id']]];
            if (!empty($data['both_directions'])) $pairs[] = [$data['destination_id'],$data['origin_id']];
            foreach ($pairs as [$from,$to]) DB::table('agenda_manual_routes')->updateOrInsert([
                'abogado_id'=>$lawyer, 'origin_id'=>$from, 'destination_id'=>$to, 'mode'=>$data['mode'],
            ], ['minutes'=>$data['minutes'], 'updated_at'=>now()]);
            app(AgendaJournal::class)->audit($lawyer, $lawyer, 'manual_route.saved', 'lawyer', $lawyer, null,
                ['origin_id'=>$data['origin_id'],'destination_id'=>$data['destination_id'],'minutes'=>$data['minutes']]);
            app(AgendaConflictService::class)->reconcile($lawyer);
        });
    }
}
