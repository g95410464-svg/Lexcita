<?php

namespace Tests\Feature;

use App\Models\BookingHold;
use App\Models\Cita;
use App\Models\HorarioDisponible;
use App\Models\Usuario;
use App\Services\CitaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SliBookingHoldTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $cliente;
    private Usuario $abogado;

    protected function setUp(): void
    {
        parent::setUp();
        config(['organization.id' => 'servicios-legales-integrados']);
        Carbon::setTestNow('2026-10-22 08:00:00');

        $this->cliente = Usuario::create([
            'nombre' => 'Cliente test', 'email' => 'cliente-hold@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'cliente', 'activo' => true,
            'email_verified_at' => now(),
        ]);
        $this->abogado = Usuario::create([
            'nombre' => 'Abogado test', 'email' => 'abogado-hold@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'abogado', 'activo' => true,
        ]);
        HorarioDisponible::create([
            'abogado_id' => $this->abogado->id, 'dia_semana' => 'viernes',
            'hora_inicio' => '09:00', 'hora_fin' => '11:00', 'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_slot_is_held_for_fifteen_minutes_then_returns_to_availability(): void
    {
        $service = app(CitaService::class);
        $hold = $service->retenerHorario($this->cliente->id, $this->abogado->id, '2026-10-23', '09:00');

        $this->assertSame('09:00', $hold->hora_inicio);
        $this->assertSame('2026-10-22 08:15', $hold->expires_at->format('Y-m-d H:i'));
        $this->assertNotContains('09:00', array_column($service->getSlotsDisponibles($this->abogado->id, '2026-10-23'), 'hora'));

        Carbon::setTestNow('2026-10-22 08:15:01');
        $this->assertContains('09:00', array_column($service->getSlotsDisponibles($this->abogado->id, '2026-10-23'), 'hora'));
    }

    public function test_second_client_cannot_hold_a_slot_already_reserved(): void
    {
        $service = app(CitaService::class);
        $service->retenerHorario($this->cliente->id, $this->abogado->id, '2026-10-23', '09:00');
        $anotherClient = Usuario::create([
            'nombre' => 'Segundo cliente', 'email' => 'cliente2-hold@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'cliente', 'activo' => true,
        ]);

        $this->expectException(\DomainException::class);
        $service->retenerHorario($anotherClient->id, $this->abogado->id, '2026-10-23', '09:00');
    }

    public function test_client_can_release_their_hold_and_another_client_can_reserve_it(): void
    {
        $service = app(CitaService::class);
        $hold = $service->retenerHorario($this->cliente->id, $this->abogado->id, '2026-10-23', '09:00');
        $service->liberarRetencion($this->cliente->id, $hold->token);
        $anotherClient = Usuario::create([
            'nombre' => 'Segundo cliente', 'email' => 'cliente2-hold@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'cliente', 'activo' => true,
        ]);

        $nextHold = $service->retenerHorario($anotherClient->id, $this->abogado->id, '2026-10-23', '09:00');
        $this->assertInstanceOf(BookingHold::class, $nextHold);
    }

    public function test_sli_appointment_keeps_the_details_step_hold_deadline_through_payment(): void
    {
        $hold = app(CitaService::class)->retenerHorario(
            $this->cliente->id, $this->abogado->id, '2026-10-23', '09:00'
        );
        Carbon::setTestNow('2026-10-22 08:05:00');

        $response = $this->actingAs($this->cliente)->post(route('cliente.nueva-cita.post'), [
            'abogado_id' => $this->abogado->id,
            'fecha' => '2026-10-23',
            'hora_inicio' => '09:00',
            'tipo' => 'consulta_general',
            'modalidad' => 'virtual',
            'hold_token' => $hold->token,
        ]);

        $cita = Cita::sole();
        $response->assertRedirect(route('cliente.pre-confirmacion', $cita->id));
        $this->assertSame('2026-10-22 08:15', $cita->hold_expires_at->format('Y-m-d H:i'));
        $this->assertTrue($cita->hold_expires_at->equalTo($hold->expires_at));
        $this->assertDatabaseMissing('booking_holds', ['token' => $hold->token]);
        $this->assertNotContains('09:00', array_column(
            app(CitaService::class)->getSlotsDisponibles($this->abogado->id, '2026-10-23'), 'hora'
        ));

        Carbon::setTestNow('2026-10-22 08:15:01');
        $this->assertContains('09:00', array_column(
            app(CitaService::class)->getSlotsDisponibles($this->abogado->id, '2026-10-23'), 'hora'
        ));
        $this->assertSame('cancelada', $cita->fresh()->estado);
        $this->assertSame('expired', $cita->fresh()->payment_status);

        $anotherClient = Usuario::create([
            'nombre' => 'Segundo cliente', 'email' => 'cliente2-hold@example.test',
            'password' => 'ClaveLocal123', 'rol' => 'cliente', 'activo' => true,
        ]);
        $newHold = app(CitaService::class)->retenerHorario(
            $anotherClient->id, $this->abogado->id, '2026-10-23', '09:00'
        );
        $this->assertSame($anotherClient->id, $newHold->cliente_id);
    }
}
