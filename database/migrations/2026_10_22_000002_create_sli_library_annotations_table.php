<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sli_library_annotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('sli_library_documents')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->string('kind', 20);
            $table->text('quote')->nullable();
            $table->text('note')->nullable();
            $table->json('rects')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'usuario_id', 'page'], 'sli_library_annotations_reader_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sli_library_annotations');
    }
};
