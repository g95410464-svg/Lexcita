<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();                   // ej: LEX-2024-0001
            $table->foreignId('cliente_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('abogado_id')->constrained('usuarios')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->enum('tipo', ['consulta_general','derecho_familiar','derecho_penal','derecho_laboral','derecho_civil','otro']);
            $table->enum('modalidad', ['presencial','virtual']);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['pendiente_pago','confirmada','cancelada'])->default('pendiente_pago');
            $table->decimal('monto', 8, 2)->default(50.00);
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
