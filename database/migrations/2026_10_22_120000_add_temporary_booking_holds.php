<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table): void {
            $table->timestamp('hold_expires_at')->nullable()->index();
        });

        // Las solicitudes de pago anteriores a esta función vencen a los 15
        // minutos desde su creación; no deben bloquear horarios indefinidamente.
        $pendingAppointments = DB::table('citas')
            ->where('estado', 'pendiente_pago')
            ->where(function ($query): void {
                $query->whereNull('payment_status')->orWhere('payment_status', '!=', 'completed');
            })
            ->get(['id', 'created_at']);

        foreach ($pendingAppointments as $appointment) {
            DB::table('citas')->where('id', $appointment->id)->update([
                'hold_expires_at' => ($appointment->created_at
                    ? \Illuminate\Support\Carbon::parse($appointment->created_at)
                    : now())->addMinutes(15),
            ]);
        }

        Schema::create('booking_holds', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('cliente_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('abogado_id')->constrained('usuarios')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['abogado_id', 'fecha', 'hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_holds');
        Schema::table('citas', function (Blueprint $table): void {
            $table->dropIndex(['hold_expires_at']);
            $table->dropColumn('hold_expires_at');
        });
    }
};
