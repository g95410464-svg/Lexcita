<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->text('perfil_biografia')->nullable();
            $table->string('perfil_imagen', 100)->nullable();
        });

        Schema::create('sli_site_settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sli_site_settings');
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['perfil_biografia', 'perfil_imagen']);
        });
    }
};
