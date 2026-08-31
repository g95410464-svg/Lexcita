<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('video_rooms', function (Blueprint $table) {
            // Garantiza a nivel de BD que una cita solo pueda tener una VideoRoom.
            $table->unique('cita_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_rooms', function (Blueprint $table) {
            $table->dropUnique(['cita_id']);
        });
    }
};
