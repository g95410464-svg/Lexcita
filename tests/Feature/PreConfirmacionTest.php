<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreConfirmacionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCliente(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Cliente Pre',
            'email'    => 'pre@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
    }

    private function makeAbogado(): Usuario
    {
        return Usuario::create([
            'nombre'   => 'Abogado Pre',
            'email'    => 'preabog@test.com',
            'password' => 'password123',
            'rol'      => 'abogado',
            'activo'   => true,
        ]);
    }

    private function makeCitaPendiente(Usuario $cliente): Cita
    {
        return Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $this->makeAbogado()->id,
            'fecha'       => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => 'presencial',
            'descripcion' => 'Cita de prueba',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);
    }

    /** El propietario de una cita pendiente de pago ve la pre-confirmación (200). */
    public function test_propietario_ve_pre_confirmacion_de_cita_pendiente(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);

        $response = $this->actingAs($cliente)
            ->get(route('cliente.pre-confirmacion', $cita->id));

        $response->assertStatus(200);
        $response->assertSee($cita->codigo, false);
    }

    /** Un usuario que NO es propietario no debe ver la pre-confirmación (404). */
    public function test_no_propietario_no_ve_pre_confirmacion(): void
    {
        $dueno = $this->makeCliente();
        $cita = $this->makeCitaPendiente($dueno);

        $otro = Usuario::create([
            'nombre'   => 'Cliente Otro',
            'email'    => 'otro@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);

        $response = $this->actingAs($otro)
            ->get(route('cliente.pre-confirmacion', $cita->id));

        $response->assertStatus(404);
    }

    /** GET /cliente/hacer-pago/{id} NO debe confirmar la cita sin pago validado. */
    public function test_hacer_pago_no_confirma_sin_pago(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);

        $response = $this->actingAs($cliente)
            ->get(route('cliente.hacer-pago', $cita->id));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
    }

    /** GET /pago/crear-sesion/{id} NO debe confirmar la cita sin pago validado. */
    public function test_procesar_pago_no_confirma_sin_pago(): void
    {
        $cliente = $this->makeCliente();
        $cita = $this->makeCitaPendiente($cliente);

        $response = $this->actingAs($cliente)
            ->get(route('pago.crear-sesion', $cita->id));

        $response->assertStatus(302);

        $cita->refresh();
        $this->assertTrue($cita->estaPendiente());
        $this->assertFalse($cita->estaConfirmada());
    }
}
