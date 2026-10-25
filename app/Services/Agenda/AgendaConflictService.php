<?php

namespace App\Services\Agenda;

use App\Models\{AgendaConflict, RescheduleProposal};
use Illuminate\Support\Str;

class AgendaConflictService
{
    public function notifyAdministrators(AgendaConflict $conflict): void
    {
        foreach (\App\Models\Usuario::where('rol','admin')->where('activo',true)->pluck('id') as $admin) {
            app(AgendaJournal::class)->notify($admin,'escalated:'.$conflict->id.':'.$admin,
                'Una cita necesita intervención del despacho. Revisa los conflictos de agenda.',route('interno.agenda.conflictos'));
        }
    }

    /** Called under the same lock by reads or the minute scheduler. */
    public function review(int $lawyer): void
    {
        $this->reconcile($lawyer);
        $expired = RescheduleProposal::whereHas('cita',fn($q)=>$q->where('abogado_id',$lawyer))
            ->where('status','enviada')->where('expires_at','<=',\Carbon\CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'))->get();
        foreach ($expired as $proposal) {
            $proposal->update(['status'=>'expirada']);
            if ($proposal->conflict->status==='en_negociacion') $proposal->conflict->update(['status'=>'abierto']);
            app(AgendaJournal::class)->notify($lawyer,'expired:'.$proposal->id,
                'Una propuesta venció sin respuesta. La cita original sigue vigente.',route('abogado.agenda'));
        }
        foreach (AgendaConflict::where('abogado_id',$lawyer)->whereIn('status',['abierto','en_negociacion','escalado'])->with('cita')->get() as $conflict) {
            $start=app(AvailabilityService::class)->businessTime($conflict->cita->fecha->toDateString(),$conflict->cita->hora_inicio);
            if ($start->lte(now()->addMinutes(config('agenda.urgent_conflict_minutes')))) {
                $conflict->update(['status'=>'escalado']);
                $this->notifyAdministrators($conflict);
            }
        }
    }

    /** Caller owns the lawyer lock. Never moves, cancels or charges a booking. */
    public function reconcile(int $lawyerId): int
    {
        $availability = app(AvailabilityService::class);
        $appointments = $availability->occupyingAppointments($lawyerId)
            ->where('fecha', '>=', today()->toDateString())->get();
        $seen = [];
        foreach ($appointments->groupBy(fn ($c) => $c->fecha->toDateString()) as $date => $group) {
            $context = $availability->context($lawyerId, $date);
            foreach ($group as $cita) {
                $start = $availability->businessTime($date, $cita->hora_inicio);
                $end = $availability->businessTime($date, $cita->hora_fin);
                if ($end->lte($start)) $end = $end->addDay();
                $issues = $availability->issues($context, $start, $end, $cita->id, null, $cita->agenda_location_id);
                foreach ($issues as $key => $issue) {
                    $conflict = AgendaConflict::firstOrNew(['cita_id'=>$cita->id, 'cause_key'=>$key]);
                    $reopened = !$conflict->exists || in_array($conflict->status, ['resuelto', 'cerrado_manual'], true);
                    $conflict->fill(['abogado_id'=>$lawyerId, 'event_id'=>$issue['event_id'], 'reason'=>$issue['reason']]);
                    if ($reopened) $conflict->fill(['status'=>'abierto', 'resolved_at'=>null]);
                    $conflict->save();
                    $seen[] = $conflict->id;
                    if ($reopened) app(AgendaJournal::class)->notify($lawyerId, 'conflict:'.$conflict->id.':'.Str::uuid(),
                        'Se detectó un conflicto con una cita. Revisar o proponer otro horario.', route('abogado.agenda'));
                }
            }
        }
        $closed = AgendaConflict::where('abogado_id', $lawyerId)->whereNotIn('id', $seen)
            ->whereIn('status', ['abierto', 'en_negociacion', 'escalado'])->pluck('id');
        AgendaConflict::whereIn('id', $closed)->update(['status'=>'cerrado_manual', 'resolved_at'=>now()]);
        RescheduleProposal::whereIn('conflict_id', $closed)->where('status', 'enviada')->update(['status'=>'retirada']);
        return count($seen);
    }
}
