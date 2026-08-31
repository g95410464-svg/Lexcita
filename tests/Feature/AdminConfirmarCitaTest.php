<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirmación manual de citas desde el panel admin (/interno/citas).
 * Verifica que la ruta interno.citas.confirmar:
 *  - permite a un admin confirmar una cita pendiente_pago vía HTTP;
 *  - NO toca payment_status (queda 'pending': confirmación administrativa);
 *  - crea exactamente una VideoRoom si modalidad = virtual;
 *  - presencial NO crea VideoRoom;
 *  - doble confirmación no duplica la sala;
 *  - un cliente NO puede llamar la ruta (redirigido por rol).
 */
class AdminConfirmarCitaTest extends TestCase
{
    use RefreshDatabase;

    private function makeUsuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre'            => ucfirst($rol),
            'email'             => $rol . uniqid() . '@test.com',
            'password'          => 'password123',
            'rol'               => $rol,
            'activo'            => true,
            'email_verified_at' => now(),
        ]);
    }

    private function makeCita(string $modalidad, string $estado = 'pendiente_pago'): Cita
    {
        $cliente = $this->makeUsuario('cliente');
        $abogado = $this->makeUsuario('abogado');

        return Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => $modalidad,
            'descripcion' => 'Cita admin',
            'estado'      => $estado,
            'monto'       => 35.0,
        ]);
    }

    /** A. Admin confirma una cita pendiente presencial vía ruta HTTP. */
    public function test_admin_confirma_pendiente_presencial(): void
    {
        $admin = $this->makeUsuario('admin');
        $cita  = $this->makeCita('presencial');

        $res = $this->actingAs($admin)
            ->post(route('interno.citas.confirmar', $cita->id));

        $res->assertStatus(302);
        $res->assertSessionHas('success');

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());

        // La confirmación administrativa NO marca el pago como completado.
        $this->assertSame('pending', $cita->payment_status);
        $this->assertNull($cita->transaction_id);
        $this->assertNull($cita->paid_at);
    }

    /** B. Admin confirma virtual → crea exactamente una VideoRoom. */
    public function test_admin_confirma_virtual_crea_una_video_room(): void
    {
        $admin = $this->makeUsuario('admin');
        $cita  = $this->makeCita('virtual');

        $this->actingAs($admin)
            ->post(route('interno.citas.confirmar', $cita->id))
            ->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertTrue($cita->esVirtual());
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** C. Presencial NO crea VideoRoom. */
    public function test_admin_confirma_presencial_no_crea_video_room(): void
    {
        $admin = $this->makeUsuario('admin');
        $cita  = $this->makeCita('presencial');

        $this->actingAs($admin)
            ->post(route('interno.citas.confirmar', $cita->id))
            ->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame(0, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** D. Doble confirmación admin no duplica la VideoRoom. */
    public function test_admin_doble_confirmacion_no_duplica_video_room(): void
    {
        $admin = $this->makeUsuario('admin');
        $cita  = $this->makeCita('virtual');

        $this->actingAs($admin)->post(route('interno.citas.confirmar', $cita->id))->assertStatus(302);
        $this->actingAs($admin)->post(route('interno.citas.confirmar', $cita->id))->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaConfirmada());
        $this->assertSame(1, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** E. Un cliente NO puede confirmar vía la ruta de admin (redirigido por rol). */
    public function test_cliente_no_puede_confirmar_via_admin(): void
    {
        $cliente = $this->makeUsuario('cliente');
        $cita    = $this->makeCita('virtual');

        $this->actingAs($cliente)
            ->post(route('interno.citas.confirmar', $cita->id))
            ->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertSame(0, VideoRoom::where('cita_id', $cita->id)->count());
    }

    /** F. Intentar confirmar una cita cancelada → 404 (la ruta la excluye). */
    public function test_admin_no_puede_confirmar_cita_cancelada(): void
    {
        $admin = $this->makeUsuario('admin');
        $cita  = $this->makeCita('presencial', 'cancelada');

        $this->actingAs($admin)
            ->post(route('interno.citas.confirmar', $cita->id))
            ->assertStatus(404);

        $cita->refresh();
        $this->assertTrue($cita->estaCancelada());
    }
}
