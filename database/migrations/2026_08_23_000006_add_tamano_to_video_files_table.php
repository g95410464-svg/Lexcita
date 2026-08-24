<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_files', function (Blueprint $table) {
            $table->unsignedBigInteger('tamano')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('video_files', function (Blueprint $table) {
            $table->dropColumn('tamano');
        });
    }
};