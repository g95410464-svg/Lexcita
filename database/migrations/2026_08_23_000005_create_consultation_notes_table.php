<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->foreignId('abogado_id')->constrained('usuarios')->onDelete('cascade');
            $table->text('notes');
            $table->timestamps();

            $table->index(['cita_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_notes');
    }
};