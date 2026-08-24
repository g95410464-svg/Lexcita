<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->string('room_token', 64)->unique(); // identificador no adivinable
            $table->enum('status', ['programada','disponible','en_espera','en_consulta','finalizada'])->default('programada');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['cita_id']);
            $table->index(['room_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_rooms');
    }
};