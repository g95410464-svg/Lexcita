<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->boolean('cliente_consintio')->default(false)->after('estado');
            $table->boolean('abogado_consintio')->default(false)->after('cliente_consintio');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['cliente_consintio', 'abogado_consintio']);
        });
    }
};