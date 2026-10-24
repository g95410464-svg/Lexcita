<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sli_library_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('category', 80)->index();
            $table->string('jurisdiction', 100)->default('El Salvador');
            $table->text('description')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('storage_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('sli_library_documents')->insert([
            ['title' => 'Buscador oficial de legislación', 'category' => 'Legislación', 'jurisdiction' => 'El Salvador',
                'description' => 'Consulta legislación por nombre, decreto y otros criterios en el Departamento de Documentación Judicial.',
                'source_url' => 'https://www.jurisprudencia.gob.sv/busqueda/busquedaLeg.php?id=2', 'storage_path' => null, 'created_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Constitución de la República', 'category' => 'Constitucional', 'jurisdiction' => 'El Salvador',
                'description' => 'Publicación de la Constitución en el portal oficial de la Asamblea Legislativa.',
                'source_url' => 'https://www.asamblea.gob.sv/node/13639', 'storage_path' => null, 'created_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Leyes y decretos por año', 'category' => 'Legislación', 'jurisdiction' => 'El Salvador',
                'description' => 'Portal de la Asamblea Legislativa para localizar leyes, decretos y acuerdos publicados.',
                'source_url' => 'https://www.asamblea.gob.sv/leyes-y-decretos/decretos-por-anios', 'storage_path' => null, 'created_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Colecciones de la Biblioteca Judicial', 'category' => 'Bibliografía jurídica', 'jurisdiction' => 'El Salvador',
                'description' => 'Catálogo de colecciones jurídicas de la Corte Suprema de Justicia; consulta las condiciones de acceso de cada material.',
                'source_url' => 'https://www.csj.gob.sv/biblioteca-judicial-colecciones/', 'storage_path' => null, 'created_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sli_library_documents');
    }
};
