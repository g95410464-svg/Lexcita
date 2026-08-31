<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica las restricciones de integridad de BD añadidas en la migración
 * add_payments_to_citas_table: UNIQUE (sobre columna nullable) de
 * paypal_order_id y transaction_id.
 *
 * - Múltiples NULL conviven (PostgreSQL y SQLite tratan cada NULL como distinto).
 * - Un mismo valor en dos citas distintas es rechazado por la BD.
 *
 * Detección portable: se comprueba el SQLSTATE "23xxx" (violación de
 * integridad) en lugar del mensaje textual específico de cada motor.
 * (SQLite: 23000; PostgreSQL: 23505.)
 */
class PayPalDatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function makeCitaCompleta(): Cita
    {
        $cliente = Usuario::create([
            'nombre'   => 'Cliente Int',
            'email'    => 'int' . uniqid() . '@test.com',
            'password' => 'password123',
            'rol'      => 'cliente',
            'activo'   => true,
        ]);
        $abogado = Usuario::create([
            'nombre'   => 'Abogado Int',
            'email'    => 'abog' . uniqid() . '@test.com',
            'password' => 'password123',
            'rol'      => 'abogado',
            'activo'   => true,
        ]);

        return Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => $cliente->id,
            'abogado_id'  => $abogado->id,
            'fecha'       => '2026-09-10',
            'hora_inicio' => '10:00:00',
            'hora_fin'    => '11:00:00',
            'tipo'        => 'consulta_general',
            'modalidad'   => 'presencial',
            'descripcion' => 'Cita de integridad',
            'estado'      => 'pendiente_pago',
            'monto'       => 35.0,
        ]);
    }

    /** TEST A — dos citas pueden tener paypal_order_id = NULL. */
    public function test_multiples_paypal_order_id_null_son_validos(): void
    {
        $c1 = $this->makeCitaCompleta();
        $c2 = $this->makeCitaCompleta();

        $this->assertNull($c1->fresh()->paypal_order_id);
        $this->assertNull($c2->fresh()->paypal_order_id);
        $this->assertEquals(2, Cita::count());
    }

    /** TEST B — dos citas NO pueden compartir el mismo paypal_order_id. */
    public function test_duplicado_paypal_order_id_es_rechazado(): void
    {
        $c1 = $this->makeCitaCompleta();
        $c1->update(['paypal_order_id' => 'ORDER-UNIQUE-TEST']);
        $this->assertSame('ORDER-UNIQUE-TEST', $c1->fresh()->paypal_order_id);

        $c2 = $this->makeCitaCompleta();
        try {
            $c2->update(['paypal_order_id' => 'ORDER-UNIQUE-TEST']);
            $this->fail('El segundo paypal_order_id duplicado debió lanzar QueryException.');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('23', (string) ($e->errorInfo[0] ?? ''),
                'SQLSTATE 23xxx esperado (violación de UNIQUE): ' . $e->getMessage());
        }
    }

    /** TEST C — dos citas pueden tener transaction_id = NULL. */
    public function test_multiples_transaction_id_null_son_validos(): void
    {
        $c1 = $this->makeCitaCompleta();
        $c2 = $this->makeCitaCompleta();

        $this->assertNull($c1->fresh()->transaction_id);
        $this->assertNull($c2->fresh()->transaction_id);
        $this->assertEquals(2, Cita::count());
    }

    /** TEST D — dos citas NO pueden compartir el mismo transaction_id. */
    public function test_duplicado_transaction_id_es_rechazado(): void
    {
        $c1 = $this->makeCitaCompleta();
        $c1->update(['transaction_id' => 'CAPTURE-UNIQUE-TEST']);
        $this->assertSame('CAPTURE-UNIQUE-TEST', $c1->fresh()->transaction_id);

        $c2 = $this->makeCitaCompleta();
        try {
            $c2->update(['transaction_id' => 'CAPTURE-UNIQUE-TEST']);
            $this->fail('El segundo transaction_id duplicado debió lanzar QueryException.');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('23', (string) ($e->errorInfo[0] ?? ''),
                'SQLSTATE 23xxx esperado (violación de UNIQUE): ' . $e->getMessage());
        }
    }
}