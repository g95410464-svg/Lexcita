<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('video_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'user_id']);
            $table->unique(['room_id', 'user_id']); // un usuario solo puede estar una vez en la sala
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_participants');
    }
};