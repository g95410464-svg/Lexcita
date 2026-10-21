@extends('layouts.app')
@section('title', 'Administrar biblioteca legal')
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">ADMINISTRACIÓN DEL DESPACHO</span><h1>Biblioteca legal</h1><p>Agrega enlaces a legislación, libros y recursos jurídicos para que estén disponibles a todas las cuentas con rol de abogado.</p></div></div>

<section class="sli-panel sli-editor-section"><header class="sli-panel-head"><div><h2>Agregar recurso</h2><p>Prioriza enlaces HTTPS a portales oficiales. También puedes adjuntar un PDF propio autorizado.</p></div></header>
<form class="sli-editor-form" action="{{ route('interno.biblioteca.store') }}" method="POST" enctype="multipart/form-data">@csrf
    <fieldset>
        <label>Título<input name="title" value="{{ old('title') }}" maxlength="180" required placeholder="Ej. Constitución de la República"></label>
        <label>Categoría<input name="category" value="{{ old('category') }}" maxlength="80" required placeholder="Ej. Constitucional, Civil, Penal"></label>
        <label>Jurisdicción<input name="jurisdiction" value="{{ old('jurisdiction', 'El Salvador') }}" maxlength="100" required></label>
        <label>Enlace HTTPS al documento o portal <span>Recomendado para documentos publicados en Internet.</span><input type="url" name="source_url" value="{{ old('source_url') }}" maxlength="2048" placeholder="https://..."></label>
        <label class="sli-library-description">Descripción breve<textarea name="description" maxlength="1200" placeholder="Qué contiene y para qué puede servir">{{ old('description') }}</textarea></label>
        <label class="sli-library-description">O adjunta un PDF <span>Opcional; máximo 25 MB. Solo archivos que el despacho tenga derecho a compartir.</span><input type="file" name="document" accept="application/pdf,.pdf"></label>
    </fieldset>
    <button class="sli-button sli-button-primary" type="submit"><span class="material-symbols-outlined" aria-hidden="true">add</span>Agregar a la biblioteca</button>
</form></section>

<section class="sli-panel sli-editor-section"><header class="sli-panel-head"><div><h2>Recursos publicados</h2><p>{{ $documents->total() }} recursos visibles para los abogados.</p></div></header>
<div class="sli-library-admin-list">
@forelse($documents as $document)
    <article><div><span class="sli-eyebrow">{{ $document->category }} · {{ $document->jurisdiction }}</span><h3>{{ $document->title }}</h3>@if($document->description)<p>{{ $document->description }}</p>@endif @if($document->source_url)<a href="{{ $document->source_url }}" target="_blank" rel="noopener noreferrer">Abrir fuente ↗</a>@else<span class="sli-library-private-file">PDF privado guardado</span>@endif</div><form action="{{ route('interno.biblioteca.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Quitar este recurso de la biblioteca?')">@csrf @method('DELETE')<button class="sli-button sli-button-outline" type="submit">Quitar</button></form></article>
@empty<div class="sli-empty"><div class="sli-empty-icon"><span class="material-symbols-outlined" aria-hidden="true">menu_book</span></div><h3>La biblioteca está lista para empezar</h3><p>Agrega enlaces oficiales y aparecerán de inmediato en el portal de cada abogado.</p></div>@endforelse
</div>
@if($documents->hasPages())<div class="sli-public-pagination">{{ $documents->links() }}</div>@endif
</section>
@endsection
