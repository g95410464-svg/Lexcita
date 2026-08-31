<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade los campos de pago (PayPal Orders API v2) a citas.
     *
     * Integridad BD: UNIQUE normal sobre paypal_order_id / transaction_id.
     * Tanto PostgreSQL como SQLite tratan cada NULL como un valor distinto en
     * una restricción UNIQUE, por lo que múltiples citas sin pago (columna en
     * NULL) conviven sin violar el constraint; a la vez se impide que dos citas
     * compartan la misma orden o la misma captura. La idempotencia del callback
     * se mantiene a nivel de aplicación como defensa primaria.
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // pending | completed | cancelled | failed
            $table->string('payment_status')->default('pending')->after('monto');
            $table->string('paypal_order_id')->nullable()->after('payment_status');
            $table->string('transaction_id')->nullable()->after('paypal_order_id');
            $table->timestamp('paid_at')->nullable()->after('transaction_id');

            // Integridad: una orden / una captura solo puede pertenecer a UNA cita.
            // UNIQUE normal sobre columna nullable → múltiples NULL permitidos.
            $table->unique('paypal_order_id');
            $table->unique('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // 1º) Eliminar los UNIQUE antes que las columnas (evita errores de rollback).
            $table->dropUnique('citas_paypal_order_id_unique');
            $table->dropUnique('citas_transaction_id_unique');

            // 2º) Eliminar las columnas.
            $table->dropColumn(['payment_status', 'paypal_order_id', 'transaction_id', 'paid_at']);
        });
    }
};
