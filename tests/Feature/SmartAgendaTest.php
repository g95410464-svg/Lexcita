<?php

namespace Tests\Feature;

use App\Models\{AgendaConflict, AgendaEvent, AgendaLocation, Cita, HorarioDisponible, RescheduleProposal, Usuario, VideoRoom};
use App\Services\Agenda\{AgendaConflictService, AgendaService, AvailabilityService, CalendarLock, ReschedulingService, TravelTimeService};
use App\Services\CitaService;
use Carbon\{Carbon, CarbonImmutable};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SmartAgendaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $lawyer;
    private Usuario $client;

    protected function setUp(): void
    {
        parent::setUp();
        config(['organization.id'=>'servicios-legales-integrados','agenda.enabled'=>true,'agenda.routing_provider'=>'manual']);
        Carbon::setTestNow('2026-10-25 08:00:00');
        $this->lawyer=$this->user('abogado'); $this->client=$this->user('cliente');
        $this->lawyer->horarios()->delete();
        foreach (['lunes','martes','miercoles','jueves','viernes'] as $day) HorarioDisponible::create([
            'abogado_id'=>$this->lawyer->id,'dia_semana'=>$day,'hora_inicio'=>'08:00','hora_fin'=>'17:00','activo'=>true]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    private function user(string $role): Usuario
    {
        return Usuario::create(['nombre'=>'Agenda '.$role,'email'=>uniqid().'@example.test','password'=>'Testing123!', 'rol'=>$role,'activo'=>true]);
    }

    private function event(array $overrides=[]): AgendaEvent
    {
        return app(AgendaService::class)->saveEvent($this->lawyer->id,array_replace([
            'title'=>'Actividad privada','type'=>'audiencia','starts_at'=>'2026-10-26T09:30','ends_at'=>'2026-10-26T10:30',
            'modality'=>'virtual','blocking'=>true,'transport_mode'=>'driving-car','margin_minutes'=>0,'private_note'=>'No revelar esta nota',
        ],$overrides));
    }

    private function slots(): array { return array_column(app(CitaService::class)->getSlotsDisponibles($this->lawyer->id,'2026-10-26'),'hora'); }

    private function paid(): Cita
    {
        $cita=Cita::create(['codigo'=>Cita::generarCodigo(),'cliente_id'=>$this->client->id,'abogado_id'=>$this->lawyer->id,
            'fecha'=>'2026-10-26','hora_inicio'=>'09:00:00','hora_fin'=>'10:00:00','tipo'=>'consulta_general','modalidad'=>'virtual',
            'estado'=>'pendiente_pago','monto'=>35,'payment_status'=>'completed','paid_at'=>now(),
            'payment_order_id'=>'TEST-ORDER','payment_transaction_id'=>'TEST-CAPTURE']);
        return app(CitaService::class)->confirmar($cita);
    }

    private function conflict(): AgendaConflict
    {
        $this->paid();
        $this->event(['starts_at'=>'2026-10-26T09:00','ends_at'=>'2026-10-26T10:00','acknowledge_conflicts'=>true]);
        return AgendaConflict::sole();
    }

    private function location(string $name, float $latitude=13.7): AgendaLocation
    {
        return app(AgendaService::class)->saveLocation($this->lawyer->id,['name'=>$name,'latitude'=>$latitude,'longitude'=>-89.2]);
    }

    public function test_partial_overlap_removes_both_slots_but_adjacent_slot_remains(): void
    {
        $this->event();
        $this->assertNotContains('09:00',$this->slots()); $this->assertNotContains('10:00',$this->slots());
        $this->assertContains('08:00',$this->slots()); $this->assertContains('11:00',$this->slots());
    }

    public function test_information_only_does_not_block(): void
    {
        $this->event(['blocking'=>false]); $this->assertContains('09:00',$this->slots());
    }

    public function test_midnight_and_weekly_recurrence_use_local_dates_and_utc_storage(): void
    {
        $event=$this->event(['starts_at'=>'2026-10-25T23:30','ends_at'=>'2026-10-26T08:30','repeat_weeks'=>2]);
        $this->assertSame('2026-10-26 05:30:00',$event->starts_at);
        $this->assertNotContains('08:00',$this->slots());
        $this->assertDatabaseCount('agenda_events',2);
        $this->assertSame(1,AgendaEvent::distinct()->count('series_id'));
    }

    public function test_activity_requires_acknowledgement_without_modifying_paid_booking(): void
    {
        $cita=$this->paid(); $before=$cita->getRawOriginal();
        try { $this->event(); $this->fail('Expected conflict acknowledgement'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('acknowledge_conflicts',$e->errors()); }
        $this->assertDatabaseCount('agenda_events',0); $this->assertDatabaseCount('agenda_conflicts',0);
        $this->assertSame($before,$cita->fresh()->getRawOriginal());
    }

    public function test_conflict_reconciliation_is_idempotent_and_cancel_resolves_it(): void
    {
        $conflict=$this->conflict();
        $noticeCount=DB::table('agenda_notifications')->count();
        app(CalendarLock::class)->run($this->lawyer->id,fn()=>app(AgendaConflictService::class)->reconcile($this->lawyer->id));
        $this->assertDatabaseCount('agenda_conflicts',1); $this->assertSame($noticeCount,DB::table('agenda_notifications')->count());
        app(AgendaService::class)->cancelEvent($this->lawyer->id,$conflict->event);
        $this->assertSame('cerrado_manual',$conflict->fresh()->status);
    }

    public function test_saving_activity_after_details_rejects_stale_checkout_and_preserves_deadline(): void
    {
        $hold=app(CitaService::class)->retenerHorario($this->client->id,$this->lawyer->id,'2026-10-26','09:00');
        $this->event();
        $this->actingAs($this->client)->post(route('cliente.nueva-cita.post'),[
            'abogado_id'=>$this->lawyer->id,'fecha'=>'2026-10-26','hora_inicio'=>'09:00','tipo'=>'consulta_general',
            'modalidad'=>'virtual','hold_token'=>$hold->token,
        ])->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('citas',0); $this->assertSame($hold->expires_at->timestamp,$hold->fresh()->expires_at->timestamp);
    }

    public function test_directions_and_margin_are_required_for_both_sides(): void
    {
        $office=$this->location('Despacho');$court=$this->location('Juzgado',13.8);
        app(AgendaService::class)->saveSettings($this->lawyer->id,['base_location_id'=>$office->id,'transport_mode'=>'driving-car','margin_minutes'=>15,'virtual_buffer_minutes'=>0]);
        $this->event(['starts_at'=>'2026-10-26T11:00','ends_at'=>'2026-10-26T12:00','modality'=>'presencial','location_id'=>$court->id]);
        $this->assertNotContains('10:00',$this->slots()); $this->assertNotContains('12:00',$this->slots());
        app(AgendaService::class)->saveManualRoute($this->lawyer->id,['origin_id'=>$office->id,'destination_id'=>$court->id,'mode'=>'driving-car','minutes'=>30]);
        $this->assertContains('09:00',$this->slots()); $this->assertNotContains('12:00',$this->slots());
        app(AgendaService::class)->saveManualRoute($this->lawyer->id,['origin_id'=>$court->id,'destination_id'=>$office->id,'mode'=>'driving-car','minutes'=>45]);
        $this->assertContains('13:00',$this->slots()); $this->assertNotContains('10:00',$this->slots());
    }

    public function test_provider_failure_never_creates_an_invented_route(): void
    {
        config(['agenda.routing_provider'=>'openrouteservice','agenda.routing_api_key'=>'testing-only']);
        Http::fake(['api.openrouteservice.org/*'=>Http::response([],503)]);
        $a=$this->location('A');$b=$this->location('B',13.8);
        $this->assertNull(app(TravelTimeService::class)->estimate($a,$b,'driving-car',CarbonImmutable::now()));
        Http::assertSentCount(1);
    }

    public function test_manual_route_is_mode_specific_and_same_location_needs_no_external_call(): void
    {
        $a=$this->location('A');$b=$this->location('B',13.8);
        app(AgendaService::class)->saveManualRoute($this->lawyer->id,['origin_id'=>$a->id,'destination_id'=>$b->id,'mode'=>'foot-walking','minutes'=>42]);
        $service=app(TravelTimeService::class);
        $this->assertSame(42,$service->estimate($a,$b,'foot-walking',CarbonImmutable::now())->minutes());
        $this->assertNull($service->estimate($a,$b,'driving-car',CarbonImmutable::now()));
        $this->assertSame(0,$service->estimate($a,$a,'driving-car',CarbonImmutable::now())->seconds);
        Http::assertNothingSent();
    }

    public function test_lawyer_feed_keeps_notes_private_and_slots_keep_original_contract(): void
    {
        $this->event();
        $this->actingAs($this->lawyer)->get(route('abogado.agenda'))->assertOk()->assertSee('Nueva actividad');
        $this->getJson(route('abogado.agenda.eventos',['start'=>'2026-10-26','end'=>'2026-10-27']))->assertOk()->assertSee('No revelar esta nota');
        $other=$this->user('abogado');
        $this->actingAs($other)->getJson(route('abogado.agenda.eventos',['start'=>'2026-10-26','end'=>'2026-10-27']))->assertOk()->assertDontSee('No revelar esta nota')->assertDontSee('Actividad privada');
        $result=$this->actingAs($this->client)->getJson(route('api.slots',['abogado_id'=>$this->lawyer->id,'fecha'=>'2026-10-26']))->assertOk()->assertDontSee('No revelar esta nota')->json();
        $this->assertSame(['hora','hora_label'],array_keys($result[0]));
    }

    public function test_other_lawyer_cannot_edit_or_delete_activity_or_use_private_location(): void
    {
        $event=$this->event();$location=$this->location('Secreto');$other=$this->user('abogado');
        $this->actingAs($other)->deleteJson(route('abogado.agenda.eventos.destroy',$event))->assertNotFound();
        $this->patchJson(route('abogado.agenda.eventos.update',$event),[])->assertNotFound();
        $this->post(route('abogado.agenda.preferencias'),['base_location_id'=>$location->id,'transport_mode'=>'driving-car','margin_minutes'=>15,'virtual_buffer_minutes'=>0])->assertNotFound();
        $this->assertNull($event->fresh()->cancelled_at);
    }

    public function test_client_cannot_access_lawyer_or_administrator_tools(): void
    {
        $this->actingAs($this->client)->get(route('abogado.agenda'))->assertRedirect();
        $this->get(route('interno.agenda.conflictos'))->assertRedirect();
        $this->assertDatabaseCount('agenda_events',0);
    }

    public function test_accept_preserves_payment_identity_and_video_room(): void
    {
        $conflict=$this->conflict();$cita=$conflict->cita;
        $before=$cita->only(['id','codigo','monto','payment_status','payment_order_id','payment_transaction_id','paid_at']);
        $room=VideoRoom::where('cita_id',$cita->id)->sole()->getRawOriginal();
        $proposal=app(ReschedulingService::class)->send($this->lawyer->id,$conflict,[['date'=>'2026-10-26','time'=>'11:00']]);
        $this->actingAs($this->client)->get(route('cliente.agenda.cambios'))->assertOk()->assertSee('Acepto este horario');
        $this->post(route('cliente.agenda.aceptar',$proposal),['option_id'=>$proposal->options()->sole()->id])->assertRedirect();
        $this->assertSame('11:00:00',$cita->fresh()->hora_inicio);
        $this->assertEquals($before,$cita->fresh()->only(array_keys($before)));
        $this->assertSame($room,VideoRoom::where('cita_id',$cita->id)->sole()->getRawOriginal());
        $this->assertSame('aceptada',$proposal->fresh()->status);$this->assertSame('resuelto',$conflict->fresh()->status);
        $this->assertDatabaseCount('citas',1);
    }

    public function test_competing_hold_rejects_acceptance_without_touching_original(): void
    {
        $conflict=$this->conflict();$service=app(ReschedulingService::class);
        $proposal=$service->send($this->lawyer->id,$conflict,[['date'=>'2026-10-26','time'=>'11:00']]);
        app(CitaService::class)->retenerHorario($this->user('cliente')->id,$this->lawyer->id,'2026-10-26','11:00');
        try { $service->accept($this->client->id,$proposal,$proposal->options()->sole()->id);$this->fail('Expected unavailable slot'); }
        catch (\DomainException) { $this->assertSame('09:00:00',$conflict->cita->fresh()->hora_inicio); }
        $this->assertSame('enviada',$proposal->fresh()->status);
    }

    public function test_expired_proposal_is_not_accepted_and_review_deduplicates_alerts(): void
    {
        $conflict=$this->conflict();$service=app(ReschedulingService::class);
        $proposal=$service->send($this->lawyer->id,$conflict,[['date'=>'2026-10-26','time'=>'11:00']]);
        Carbon::setTestNow('2026-10-26 08:01:00');
        $this->actingAs($this->client)->post(route('cliente.agenda.aceptar',$proposal),['option_id'=>$proposal->options()->sole()->id])->assertSessionHasErrors('agenda');
        $admin=$this->user('admin');
        $this->artisan('agenda:review')->assertSuccessful();$count=DB::table('agenda_notifications')->count();
        $this->artisan('agenda:review')->assertSuccessful();$this->assertSame($count,DB::table('agenda_notifications')->count());
        $this->assertSame('expirada',$proposal->fresh()->status);
        $this->assertDatabaseHas('agenda_notifications',['usuario_id'=>$admin->id]);
        $this->actingAs($admin)->get(route('interno.agenda.conflictos'))->assertOk();
    }

    public function test_other_client_cannot_read_accept_or_reject_proposal(): void
    {
        $proposal=app(ReschedulingService::class)->send($this->lawyer->id,$this->conflict(),[['date'=>'2026-10-26','time'=>'11:00']]);
        $other=$this->user('cliente');
        $this->actingAs($other)->get(route('cliente.agenda.cambios'))->assertOk()->assertDontSee('Acepto este horario');
        $this->post(route('cliente.agenda.aceptar',$proposal),['option_id'=>$proposal->options()->sole()->id])->assertNotFound();
        $this->post(route('cliente.agenda.responder',$proposal),['response'=>'no_puedo'])->assertNotFound();
    }

    public function test_rejection_preserves_booking_and_later_dates_require_consent(): void
    {
        $conflict=$this->conflict();$service=app(ReschedulingService::class);
        $proposal=$service->send($this->lawyer->id,$conflict,[['date'=>'2026-10-26','time'=>'11:00']]);
        $this->assertFalse(collect($service->suggestions($conflict))->contains('date','2026-10-28'));
        $service->respond($this->client->id,$proposal,'otras',[
            ['start'=>'2026-10-28T13:00','end'=>'2026-10-28T16:00'],
        ],'Puedo el miércoles',true);
        $suggestions=$service->suggestions($conflict);
        $this->assertNotEmpty($suggestions);$this->assertSame(['2026-10-28'],array_values(array_unique(array_column($suggestions,'date'))));
        $this->assertSame('09:00:00',$conflict->cita->fresh()->hora_inicio);
    }

    public function test_base_location_change_requires_updated_proposal(): void
    {
        $conflict=$this->conflict();$service=app(ReschedulingService::class);
        $proposal=$service->send($this->lawyer->id,$conflict,[['date'=>'2026-10-26','time'=>'11:00']]);
        $location=$this->location('Nuevo despacho');
        app(AgendaService::class)->saveSettings($this->lawyer->id,['base_location_id'=>$location->id,'transport_mode'=>'driving-car','margin_minutes'=>15,'virtual_buffer_minutes'=>0]);
        $this->expectException(\DomainException::class);$service->accept($this->client->id,$proposal,$proposal->options()->sole()->id);
    }

    public function test_module_can_be_disabled_and_base_lexcita_is_untouched(): void
    {
        config(['agenda.enabled'=>false]);$this->event();$this->assertContains('09:00',$this->slots());
        $this->actingAs($this->lawyer)->get(route('abogado.agenda'))->assertOk()->assertDontSee('data-smart-agenda');
        config(['agenda.enabled'=>true,'organization.id'=>'lexcita']);
        $this->getJson(route('abogado.agenda.eventos',['start'=>'2026-10-26','end'=>'2026-10-27']))->assertNotFound();
    }

    public function test_client_refusal_notifies_administration_without_cancelling(): void
    {
        $admin=$this->user('admin');$conflict=$this->conflict();
        $proposal=app(ReschedulingService::class)->send($this->lawyer->id,$conflict,[['date'=>'2026-10-26','time'=>'11:00']]);
        $this->actingAs($this->client)->post(route('cliente.agenda.responder',$proposal),['response'=>'no_puedo'])->assertSessionHasNoErrors();
        $this->assertSame('escalado',$conflict->fresh()->status);$this->assertSame('confirmada',$conflict->cita->fresh()->estado);
        $this->assertDatabaseHas('agenda_notifications',['usuario_id'=>$admin->id]);Http::assertNothingSent();
    }

    public function test_new_activity_api_validates_location_and_supports_edit_then_retire(): void
    {
        $data=['title'=>'Visita','type'=>'visita','starts_at'=>'2026-10-26T14:00','ends_at'=>'2026-10-26T15:00',
            'modality'=>'presencial','blocking'=>true,'transport_mode'=>'driving-car','margin_minutes'=>15];
        $this->actingAs($this->lawyer)->postJson(route('abogado.agenda.eventos.store'),$data)->assertUnprocessable()->assertJsonValidationErrors('location_id');
        $data['modality']='virtual';
        $id=$this->postJson(route('abogado.agenda.eventos.store'),$data)->assertCreated()->json('id');
        $this->assertNotContains('14:00',$this->slots());
        $data['blocking']=false;
        $this->patchJson(route('abogado.agenda.eventos.update',$id),$data)->assertOk();
        $this->assertContains('14:00',$this->slots());
        $this->deleteJson(route('abogado.agenda.eventos.destroy',$id))->assertOk();
        $this->assertNotNull(AgendaEvent::findOrFail($id)->cancelled_at);
    }

    public function test_provider_success_is_cached_but_directed_and_bounded(): void
    {
        config(['agenda.routing_provider'=>'openrouteservice','agenda.routing_api_key'=>'testing-only']);
        Http::fake(['api.openrouteservice.org/*'=>Http::response(['routes'=>[['summary'=>['duration'=>1200,'distance'=>9000]]]],200)]);
        $a=$this->location('A');$b=$this->location('B',13.8);$service=app(TravelTimeService::class);
        $this->assertSame(20,$service->estimate($a,$b,'driving-car',CarbonImmutable::now())->minutes());
        $service->estimate($a,$b,'driving-car',CarbonImmutable::now()->addHour());Http::assertSentCount(1);
        $service->estimate($b,$a,'driving-car',CarbonImmutable::now());Http::assertSentCount(2);
        $c=$this->location('C',13.9);$d=$this->location('D',14.0);
        $service->estimate($a,$c,'driving-car',CarbonImmutable::now());
        $service->estimate($a,$d,'driving-car',CarbonImmutable::now());
        $this->assertNull($service->estimate($d,$a,'driving-car',CarbonImmutable::now()));Http::assertSentCount(4);
    }
}
