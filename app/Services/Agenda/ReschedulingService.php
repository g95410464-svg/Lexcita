<?php

namespace App\Services\Agenda;

use App\Models\{AgendaConflict, Cita, RescheduleOption, RescheduleProposal};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReschedulingService
{
    public function suggestions(AgendaConflict $conflict): array
    {
        $cita = $conflict->cita;
        $preferences = DB::table('agenda_client_availability')->where('cita_id', $cita->id)->latest('id')->first();
        $windows = $preferences ? json_decode($preferences->windows, true, 512, JSON_THROW_ON_ERROR) : [];
        $first = CarbonImmutable::parse($cita->fecha->toDateString(), config('agenda.timezone'));
        $choices = [];
        for ($day = 0; $day < ($preferences?->allow_later_dates ? 8 : 2); $day++) {
            $date = $first->addDays($day)->toDateString();
            foreach (app(AvailabilityService::class)->slots($cita->abogado_id, $date, $cita->id) as $slot) {
                $time = app(AvailabilityService::class)->businessTime($date, $slot['hora']);
                if ($windows && !collect($windows)->contains(fn ($w) => $time->gte(CarbonImmutable::parse($w['start'], 'UTC'))
                    && $time->addHour()->lte(CarbonImmutable::parse($w['end'], 'UTC')))) continue;
                $choices[] = ['date'=>$date,'time'=>$slot['hora'],'label'=>$date.' · '.$slot['hora_label']];
            }
        }
        return $choices;
    }

    public function send(int $lawyer, AgendaConflict $conflict, array $options): RescheduleProposal
    {
        return app(CalendarLock::class)->run($lawyer, function () use ($lawyer, $conflict, $options) {
            $conflict = AgendaConflict::where('abogado_id', $lawyer)->lockForUpdate()->findOrFail($conflict->id);
            $cita = Cita::whereKey($conflict->cita_id)->lockForUpdate()->firstOrFail();
            if (!$cita->estaConfirmada() || in_array($conflict->status, ['resuelto','cerrado_manual'], true)) {
                throw new \DomainException('Esta cita no admite una propuesta de cambio.');
            }
            $valid = collect($this->suggestions($conflict))->map(fn ($s) => $s['date'].' '.$s['time']);
            foreach ($options as $option) {
                if (!$valid->contains($option['date'].' '.$option['time'])) {
                    throw new \DomainException('Una opción dejó de estar disponible o necesita autorización del cliente para explorar otros días.');
                }
            }
            RescheduleProposal::where('cita_id', $cita->id)->where('status','enviada')->update(['status'=>'retirada']);
            $proposal = RescheduleProposal::create(['conflict_id'=>$conflict->id,'cita_id'=>$cita->id,'created_by'=>$lawyer,
                'expires_at'=>CarbonImmutable::now('UTC')->addHours(config('agenda.proposal_hours'))->format('Y-m-d H:i:s'), 'status'=>'enviada']);
            $base = DB::table('agenda_settings')->where('abogado_id', $lawyer)->value('base_location_id');
            foreach ($options as $option) {
                $start = app(AvailabilityService::class)->businessTime($option['date'], $option['time'])->utc();
                $proposal->options()->create(['starts_at'=>$start->format('Y-m-d H:i:s'),
                    'ends_at'=>$start->addHour()->format('Y-m-d H:i:s'), 'location_id'=>$base]);
            }
            $conflict->update(['status'=>'en_negociacion']);
            app(AgendaJournal::class)->notify($cita->cliente_id, 'proposal:'.$proposal->id,
                'Tu abogado propone cambiar una cita. Elige un horario o solicita otro.', route('cliente.agenda.cambios'));
            app(AgendaJournal::class)->audit($lawyer,$lawyer,'proposal.sent','proposal',$proposal->id,null,['cita_id'=>$cita->id]);
            return $proposal;
        });
    }

    public function accept(int $client, RescheduleProposal $proposal, int $optionId): Cita
    {
        $original = $proposal->cita;
        abort_unless($original->cliente_id === $client, 404);
        return app(CalendarLock::class)->run($original->abogado_id, function () use ($client, $proposal, $optionId, $original) {
            $cita = Cita::whereKey($original->id)->lockForUpdate()->firstOrFail();
            $proposal = RescheduleProposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            abort_unless($cita->cliente_id === $client, 404);
            if (!$cita->estaConfirmada() || $proposal->status !== 'enviada' || $proposal->expiry()->lte(now())) {
                throw new \DomainException('La propuesta ya no está disponible. Solicita otras opciones.');
            }
            $option = $proposal->options()->where('status','ofrecida')->findOrFail($optionId);
            $base = DB::table('agenda_settings')->where('abogado_id',$cita->abogado_id)->value('base_location_id');
            if ((string)$base !== (string)$option->location_id) {
                throw new \DomainException('La ubicación de atención cambió. Solicita una propuesta actualizada; tu cita original sigue vigente.');
            }
            $local = $option->start()->setTimezone(config('agenda.timezone'));
            $available = app(AvailabilityService::class)->slots($cita->abogado_id, $local->toDateString(), $cita->id);
            if (!in_array($local->format('H:i'), array_column($available,'hora'), true)) {
                throw new \DomainException('Ese horario ya no está disponible. La cita original sigue intacta; solicita otras opciones.');
            }
            $before = $cita->only(['fecha','hora_inicio','hora_fin','agenda_location_id']);
            $cita->update(['fecha'=>$local->toDateString(),'hora_inicio'=>$local->format('H:i:s'),
                'hora_fin'=>$local->addHour()->format('H:i:s'),'agenda_location_id'=>$option->location_id]);
            // Do not call confirmar(), payment capture or video-room creation.
            $proposal->update(['status'=>'aceptada','responded_at'=>now()]);
            $proposal->options()->update(['status'=>'caducada']);
            $option->update(['status'=>'elegida']);
            RescheduleProposal::where('cita_id',$cita->id)->where('status','enviada')->update(['status'=>'retirada']);
            AgendaConflict::where('cita_id',$cita->id)->whereIn('status',['abierto','en_negociacion','escalado'])
                ->update(['status'=>'resuelto','resolved_at'=>now()]);
            app(AgendaJournal::class)->audit($cita->abogado_id,$client,'appointment.rescheduled','cita',$cita->id,
                $before,$cita->only(['fecha','hora_inicio','hora_fin','agenda_location_id']));
            foreach ([$client,$cita->abogado_id] as $user) app(AgendaJournal::class)->notify($user,
                'accepted:'.$proposal->id.':'.$user, 'El cambio de horario de tu cita fue confirmado.',
                $user === $client ? route('cliente.mis-citas') : route('abogado.agenda'));
            app(AgendaConflictService::class)->reconcile($cita->abogado_id);
            return $cita->fresh();
        });
    }

    public function respond(int $client, RescheduleProposal $proposal, string $response, array $windows = [], ?string $comment = null, bool $allowLater = false): void
    {
        $original = $proposal->cita;
        abort_unless($original->cliente_id === $client, 404);
        app(CalendarLock::class)->run($original->abogado_id, function () use ($client,$proposal,$response,$windows,$comment,$allowLater,$original): void {
            $proposal = RescheduleProposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            if (!$original->fresh()->estaConfirmada() || $proposal->status !== 'enviada' || $proposal->expiry()->lte(now())) {
                throw new \DomainException('La propuesta ya no está disponible. Tu cita no cambió.');
            }
            $normalized = [];
            foreach ($windows as $window) {
                $start = CarbonImmutable::parse($window['start'], config('agenda.timezone'));
                $end = CarbonImmutable::parse($window['end'], config('agenda.timezone'));
                if (!$start->gt(now()) || !$end->gt($start) || $end->gt($start->addDays(7))) {
                    throw new \DomainException('Revisa las fechas y duración de tus ventanas disponibles.');
                }
                $normalized[] = ['start'=>$start->utc()->toIso8601String(),'end'=>$end->utc()->toIso8601String()];
            }
            DB::table('agenda_client_availability')->insert(['cita_id'=>$original->id,'cliente_id'=>$client,
                'windows'=>json_encode($normalized,JSON_THROW_ON_ERROR),'comment'=>$comment,'allow_later_dates'=>$allowLater,
                'created_at'=>now(),'updated_at'=>now()]);
            $proposal->update(['status'=>'rechazada','responded_at'=>now()]);
            $proposal->conflict->update(['status'=>$response === 'no_puedo' ? 'escalado' : 'abierto']);
            if ($response === 'no_puedo') app(AgendaConflictService::class)->notifyAdministrators($proposal->conflict);
            app(AgendaJournal::class)->notify($original->abogado_id,'reply:'.$proposal->id,
                'El cliente respondió a la propuesta. Revisa la agenda para continuar.',route('abogado.agenda'));
            app(AgendaJournal::class)->audit($original->abogado_id,$client,'proposal.'.$response,'proposal',$proposal->id,null,null);
        });
    }

    public function withdraw(int $lawyer, RescheduleProposal $proposal): void
    {
        app(CalendarLock::class)->run($lawyer, function () use ($lawyer,$proposal): void {
            $proposal = RescheduleProposal::where('created_by',$lawyer)->lockForUpdate()->findOrFail($proposal->id);
            if ($proposal->status === 'enviada') {
                $proposal->update(['status'=>'retirada']);
                $proposal->conflict->update(['status'=>'abierto']);
            }
        });
    }
}
