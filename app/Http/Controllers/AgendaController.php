<?php

namespace App\Http\Controllers;

use App\Models\{AgendaConflict, AgendaEvent, AgendaLocation, Cita};
use App\Services\Agenda\{AgendaService, AvailabilityService, ReschedulingService, TravelTimeService};
use App\Services\Agenda\Routing\OpenRouteServiceProvider;
use App\Support\AgendaModule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgendaController extends Controller
{
    private function ready(): void
    {
        abort_unless(config('organization.id') === 'servicios-legales-integrados', 404);
        abort_unless(app(AgendaModule::class)->ready(), 503, 'La agenda se está preparando. Vuelve a intentarlo en unos minutos.');
    }

    public function index(Request $request)
    {
        $this->ready();
        $lawyer = $request->user()->id;
        app(\App\Services\Agenda\CalendarLock::class)->run($lawyer, fn()=>app(\App\Services\Agenda\AgendaConflictService::class)->review($lawyer));
        $locations = AgendaLocation::where('abogado_id',$lawyer)->where('active',true)->orderBy('name')->get();
        $settings = DB::table('agenda_settings')->where('abogado_id',$lawyer)->first();
        $manualRoutes = DB::table('agenda_manual_routes')->where('abogado_id',$lawyer)->get();
        $conflicts = AgendaConflict::where('abogado_id',$lawyer)->whereIn('status',['abierto','en_negociacion','escalado'])
            ->with(['cita.cliente','proposals.options'])->orderBy('created_at')->get();
        $notifications = DB::table('agenda_notifications')->where('usuario_id',$lawyer)->whereNull('read_at')->latest('id')->limit(20)->get();
        return view('sli.abogado.agenda-inteligente',compact('locations','settings','manualRoutes','conflicts','notifications'));
    }

    public function feed(Request $request, AvailabilityService $availability)
    {
        $this->ready();
        $data = $request->validate(['start'=>'required|date','end'=>'required|date|after:start']);
        $start = CarbonImmutable::parse($data['start'],config('agenda.timezone'));
        $end = CarbonImmutable::parse($data['end'],config('agenda.timezone'));
        abort_if($end->gt($start->addDays(93)),422,'Consulta como máximo tres meses.');
        $lawyer = $request->user()->id;
        $settings = DB::table('agenda_settings')->where('abogado_id',$lawyer)->first();
        $locations = AgendaLocation::where('abogado_id',$lawyer)->where('active',true)->get()->keyBy('id');
        $items = [];
        $timeline = [];
        $appointments = $availability->occupyingAppointments($lawyer)
            ->whereBetween('fecha',[$start->subDay()->toDateString(),$end->toDateString()])->with(['cliente','videoRoom'])->get();
        foreach ($appointments as $cita) {
            $from = $availability->businessTime($cita->fecha->toDateString(),$cita->hora_inicio);
            $to = $availability->businessTime($cita->fecha->toDateString(),$cita->hora_fin);
            if ($to->lte($from)) $to=$to->addDay();
            $items[] = ['id'=>'cita-'.$cita->id,'title'=>($cita->estaPendiente()?'Pago pendiente · ':'').$cita->cliente->nombre,
                'start'=>$from->toIso8601String(),'end'=>$to->toIso8601String(),'color'=>$cita->estaPendiente()?'#ac803e':'#173b59',
                'extendedProps'=>['kind'=>'appointment','url_sala'=>$cita->estaConfirmada()&&$cita->videoRoom
                    ? route('video.sala',$cita->videoRoom->room_token):null]];
            $timeline[]=['start'=>$from,'end'=>$to,'location'=>$cita->agenda_location_id??$settings?->base_location_id];
        }
        foreach (AgendaEvent::where('abogado_id',$lawyer)->whereNull('cancelled_at')
            ->where('starts_at','<',$end->utc()->format('Y-m-d H:i:s'))
            ->where('ends_at','>',$start->utc()->format('Y-m-d H:i:s'))->get() as $event) {
            $items[]=['id'=>'event-'.$event->id,'title'=>$event->title,
                'start'=>$event->start()->setTimezone(config('agenda.timezone'))->toIso8601String(),
                'end'=>$event->end()->setTimezone(config('agenda.timezone'))->toIso8601String(),
                'color'=>$event->blocking?'#6e5743':'#69847a','extendedProps'=>[
                    'kind'=>'activity','event_id'=>$event->id,'type'=>$event->type,'modality'=>$event->modality,
                    'local_start'=>$event->start()->setTimezone(config('agenda.timezone'))->format('Y-m-d\TH:i'),
                    'local_end'=>$event->end()->setTimezone(config('agenda.timezone'))->format('Y-m-d\TH:i'),
                    'blocking'=>$event->blocking,'location_id'=>$event->location_id,'transport_mode'=>$event->transport_mode,
                    'margin_minutes'=>$event->margin_minutes,'private_note'=>$event->private_note,
                ]];
            if ($event->blocking) $timeline[]=['start'=>$event->start(),'end'=>$event->end(),
                'location'=>$event->location_id??($event->modality==='virtual'?$settings?->base_location_id:null)];
        }
        $timeline=collect($timeline)->sortBy(fn ($t)=>$t['start']->timestamp)->values();
        for ($i=1;$i<$timeline->count();$i++) {
            $previous=$timeline[$i-1];$next=$timeline[$i];
            if ($previous['end']->gt($next['start'])) continue;
            $estimate=app(TravelTimeService::class)->estimate($locations->get($previous['location']),
                $locations->get($next['location']),$settings?->transport_mode??'driving-car',$previous['end']);
            if (!$estimate || !$estimate->seconds) continue;
            $margin=(int)($settings?->margin_minutes??config('agenda.margin_minutes'));
            $items[]=['title'=>'Traslado estimado: '.$estimate->minutes().' min + '.$margin.' min · '.
                ($estimate->quality==='manual'?'manual':'sin tráfico'),
                'start'=>$previous['end']->setTimezone(config('agenda.timezone'))->toIso8601String(),
                'end'=>$previous['end']->addMinutes($estimate->minutes()+$margin)->setTimezone(config('agenda.timezone'))->toIso8601String(),
                'color'=>'#a47831','display'=>'background','extendedProps'=>['kind'=>'travel']];
        }
        foreach ($request->user()->horarios()->where('activo',true)->get() as $hours) {
            $day=\App\Models\HorarioDisponible::diaSemanaMap()[$hours->dia_semana]??null;
            if ($day) $items[]=['title'=>'Horario habitual','daysOfWeek'=>[$day],'startTime'=>$hours->hora_inicio,
                'endTime'=>$hours->hora_fin,'display'=>'background','color'=>'#dce8f0','extendedProps'=>['kind'=>'hours']];
        }
        return response()->json($items)->header('Cache-Control','private, no-store');
    }

    public function storeEvent(Request $request, AgendaService $agenda)
    {
        $this->ready();
        $event=$agenda->saveEvent($request->user()->id,$this->eventData($request));
        return response()->json(['message'=>'Actividad guardada. Las citas existentes conservan su horario.','id'=>$event->id],201);
    }

    public function updateEvent(Request $request, AgendaEvent $event, AgendaService $agenda)
    {
        $this->ready();
        abort_unless($event->abogado_id===$request->user()->id,404);
        $agenda->saveEvent($request->user()->id,$this->eventData($request),$event);
        return response()->json(['message'=>'Actividad actualizada.']);
    }

    public function destroyEvent(Request $request, AgendaEvent $event, AgendaService $agenda)
    {
        $this->ready();
        $agenda->cancelEvent($request->user()->id,$event);
        return response()->json(['message'=>'Actividad retirada. La disponibilidad se recalculará.']);
    }

    private function eventData(Request $request): array
    {
        return $request->validate([
            'title'=>'required|string|max:180','type'=>'required|in:audiencia,diligencia,reunion,visita,personal,descanso,otro',
            'starts_at'=>'required|date','ends_at'=>'required|date|after:starts_at','modality'=>'required|in:virtual,presencial',
            'location_id'=>'nullable|integer','blocking'=>'required|boolean','private_note'=>'nullable|string|max:2000',
            'transport_mode'=>['required',Rule::in(OpenRouteServiceProvider::MODES)],
            'margin_minutes'=>'required|integer|min:0|max:180','repeat_weeks'=>'sometimes|integer|min:1|max:12',
            'acknowledge_conflicts'=>'sometimes|boolean',
        ]);
    }

    public function saveLocation(Request $request, AgendaService $agenda, ?AgendaLocation $location=null)
    {
        $this->ready();
        $data=$request->validate(['name'=>'required|string|max:120','address'=>'nullable|string|max:255',
            'latitude'=>'required|numeric|between:-90,90','longitude'=>'required|numeric|between:-180,180','active'=>'sometimes|boolean']);
        $agenda->saveLocation($request->user()->id,$data,$location);
        return back()->with('success','Ubicación guardada. Revisa los trayectos manuales que usaban esta ubicación.');
    }

    public function saveSettings(Request $request, AgendaService $agenda)
    {
        $this->ready();
        $data=$request->validate(['base_location_id'=>'nullable|integer','day_start_location_id'=>'nullable|integer',
            'day_end_location_id'=>'nullable|integer','transport_mode'=>['required',Rule::in(OpenRouteServiceProvider::MODES)],
            'margin_minutes'=>'required|integer|min:0|max:180','virtual_buffer_minutes'=>'required|integer|min:0|max:60']);
        $agenda->saveSettings($request->user()->id,$data);
        return back()->with('success','Preferencias actualizadas. Las citas existentes no se han movido.');
    }

    public function saveManualRoute(Request $request, AgendaService $agenda)
    {
        $this->ready();
        $data=$request->validate(['origin_id'=>'required|integer','destination_id'=>'required|integer|different:origin_id',
            'mode'=>['required',Rule::in(OpenRouteServiceProvider::MODES)],'minutes'=>'required|integer|min:1|max:720',
            'both_directions'=>'sometimes|boolean']);
        $agenda->saveManualRoute($request->user()->id,$data);
        return back()->with('success','Estimación manual guardada. El margen se suma por separado.');
    }

    public function suggestions(Request $request, AgendaConflict $conflict, ReschedulingService $service)
    {
        $this->ready();
        abort_unless($conflict->abogado_id===$request->user()->id,404);
        return response()->json($service->suggestions($conflict))->header('Cache-Control','private, no-store');
    }

    public function sendProposal(Request $request, AgendaConflict $conflict, ReschedulingService $service)
    {
        $this->ready();
        abort_unless($conflict->abogado_id===$request->user()->id,404);
        $data=$request->validate(['options'=>'required|array|min:1|max:5','options.*'=>'array:date,time',
            'options.*.date'=>'required|date_format:Y-m-d','options.*.time'=>'required|date_format:H:i']);
        try { $service->send($request->user()->id,$conflict,$data['options']); }
        catch (\DomainException $e) { return response()->json(['message'=>$e->getMessage()],409); }
        return response()->json(['message'=>'Propuesta enviada. La cita no cambiará hasta que el cliente acepte.'],201);
    }

    public function readNotification(Request $request, int $notification)
    {
        $this->ready();
        $row=DB::table('agenda_notifications')->where('usuario_id',$request->user()->id)->where('id',$notification);
        abort_unless($row->exists(),404);
        $row->update(['read_at'=>now()]);
        return back();
    }

    public function administrator(Request $request)
    {
        $this->ready();
        $conflicts=AgendaConflict::whereIn('status',['abierto','en_negociacion','escalado'])
            ->with(['cita.abogado','cita.cliente'])->orderByRaw("CASE WHEN status = 'escalado' THEN 0 ELSE 1 END")->latest()->paginate(30);
        return view('sli.interno.agenda-conflictos',compact('conflicts'));
    }
}
