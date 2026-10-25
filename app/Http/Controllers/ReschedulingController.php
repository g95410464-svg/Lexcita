<?php

namespace App\Http\Controllers;

use App\Models\RescheduleProposal;
use App\Services\Agenda\ReschedulingService;
use App\Support\AgendaModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReschedulingController extends Controller
{
    private function ready(): void { abort_unless(app(AgendaModule::class)->ready(),404); }

    public function index(Request $request)
    {
        $this->ready();
        $proposals=RescheduleProposal::whereHas('cita',fn($q)=>$q->where('cliente_id',$request->user()->id))
            ->whereIn('status',['enviada','aceptada','rechazada','expirada','retirada'])
            ->with(['cita.abogado','options'])->latest()->paginate(15);
        $notifications=DB::table('agenda_notifications')->where('usuario_id',$request->user()->id)
            ->whereNull('read_at')->latest('id')->limit(20)->get();
        return view('sli.cliente.cambios-horario',compact('proposals','notifications'));
    }

    public function accept(Request $request, RescheduleProposal $proposal, ReschedulingService $service)
    {
        $this->ready();
        $data=$request->validate(['option_id'=>'required|integer']);
        try { $service->accept($request->user()->id,$proposal,(int)$data['option_id']); }
        catch (\DomainException $e) { return back()->withErrors(['agenda'=>$e->getMessage()]); }
        return redirect()->route('cliente.agenda.cambios')->with('success','Aceptaste el cambio. Tu cita conserva su pago y su comprobante.');
    }

    public function respond(Request $request, RescheduleProposal $proposal, ReschedulingService $service)
    {
        $this->ready();
        $data=$request->validate(['response'=>'required|in:otras,no_puedo','windows'=>'sometimes|array|max:5',
            'windows.*'=>'array:start,end','windows.*.start'=>'required|date','windows.*.end'=>'required|date',
            'comment'=>'nullable|string|max:500','allow_later_dates'=>'sometimes|boolean']);
        try { $service->respond($request->user()->id,$proposal,$data['response'],$data['windows']??[],
            $data['comment']??null,(bool)($data['allow_later_dates']??false)); }
        catch (\DomainException $e) { return back()->withErrors(['agenda'=>$e->getMessage()])->withInput(); }
        return back()->with('success','Tu respuesta fue enviada. Tu cita original no ha cambiado.');
    }

    public function withdraw(Request $request, RescheduleProposal $proposal, ReschedulingService $service)
    {
        $this->ready();
        $service->withdraw($request->user()->id,$proposal);
        return back()->with('success','Propuesta retirada. La cita original sigue vigente.');
    }
}
