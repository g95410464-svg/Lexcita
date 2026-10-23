@extends('layouts.app')
@section('title', 'Administrar biblioteca legal')
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">ADMINISTRACIÓN DEL DESPACHO</span><h1>Biblioteca legal</h1><p>Solo administración puede publicar y retirar libros. Cada recurso publicado aparece en la biblioteca de todos los abogados.</p></div></div>

<section class="sli-panel sli-editor-section"><header class="sli-panel-head"><div><h2>Publicar libro PDF</h2><p>Su primera página será la portada. Todos los abogados podrán abrirlo en el lector y guardar sus propias notas y subrayados.</p></div></header>
<form class="sli-editor-form" action="{{ route('interno.biblioteca.store') }}" method="POST" enctype="multipart/form-data">@csrf
    <fieldset>
        <label>Título<input name="title" value="{{ old('title') }}" maxlength="180" required placeholder="Ej. Constitución de la República"></label>
        <label>Categoría<input name="category" value="{{ old('category') }}" maxlength="80" required placeholder="Ej. Constitucional, Civil, Penal"></label>
        <label>Jurisdicción<input name="jurisdiction" value="{{ old('jurisdiction', 'El Salvador') }}" maxlength="100" required></label>
        <label>Fuente oficial (opcional)<input type="url" name="source_url" value="{{ old('source_url') }}" maxlength="2048" placeholder="https://..."></label>
        <label class="sli-library-description">Descripción breve<textarea name="description" maxlength="1200" placeholder="Qué contiene y para qué puede servir">{{ old('description') }}</textarea></label>
        <label class="sli-library-description">Archivo PDF <span>Máximo 25 MB. La portada se toma automáticamente de la primera página.</span><input type="file" name="document" accept="application/pdf,.pdf" required></label>
    </fieldset>
    <button class="sli-button sli-button-primary" type="submit"><span class="material-symbols-outlined" aria-hidden="true">add</span>Publicar libro</button>
</form></section>

<details class="sli-library-sources"><summary>Agregar una fuente externa<span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary><p>Se mostrará en Fuentes oficiales. Para leer y anotar dentro del sistema, publica el archivo PDF en el formulario anterior.</p>
<form class="sli-editor-form" action="{{ route('interno.biblioteca.store') }}" method="POST">@csrf
    <fieldset><label>Título<input name="title" maxlength="180" required></label><label>Categoría<input name="category" maxlength="80" required></label><label>Jurisdicción<input name="jurisdiction" value="El Salvador" maxlength="100" required></label><label>Enlace HTTPS<input type="url" name="source_url" maxlength="2048" placeholder="https://..." required></label></fieldset>
    <button class="sli-button sli-button-outline" type="submit">Agregar fuente</button>
</form></details>

<section class="sli-panel sli-editor-section"><header class="sli-panel-head"><div><h2>Recursos publicados</h2><p>{{ $documents->total() }} recursos visibles para los abogados.</p></div></header>
<div class="sli-library-admin-list">
@forelse($documents as $document)
    <article><div><span class="sli-eyebrow">{{ $document->category }} · {{ $document->jurisdiction }}</span><h3>{{ $document->title }}</h3>@if($document->description)<p>{{ $document->description }}</p>@endif @if($document->source_url)<a href="{{ $document->source_url }}" target="_blank" rel="noopener noreferrer">Abrir fuente ↗</a>@else<span class="sli-library-private-file">PDF privado guardado</span>@endif</div><form action="{{ route('interno.biblioteca.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Quitar este recurso de la biblioteca?')">@csrf @method('DELETE')<button class="sli-button sli-button-outline" type="submit">Quitar</button></form></article>
@empty<div class="sli-empty"><div class="sli-empty-icon"><span class="material-symbols-outlined" aria-hidden="true">menu_book</span></div><h3>La biblioteca está lista para empezar</h3><p>Publica tu primer PDF y aparecerá con su portada en el portal de cada abogado.</p></div>@endforelse
</div>
@if($documents->hasPages())<div class="sli-public-pagination">{{ $documents->links() }}</div>@endif
</section>
@endsection
