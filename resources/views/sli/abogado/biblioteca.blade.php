@extends('layouts.app')
@section('title', 'Biblioteca legal')
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">RECURSOS PARA TU PRÁCTICA</span><h1>Biblioteca legal</h1><p>Libros y legislación compartidos por el despacho para todo el equipo legal. Lee y guarda tus propias anotaciones.</p></div></div>

<form class="sli-library-filters" action="{{ route('abogado.biblioteca') }}" method="GET">
    <label class="sli-library-search"><span class="material-symbols-outlined" aria-hidden="true">search</span><span class="sr-only">Buscar en la biblioteca</span><input id="sli-library-query" name="q" value="{{ $search }}" placeholder="Buscar por título, tema o jurisdicción" maxlength="120"></label>
    <label class="sli-library-category"><span class="sr-only">Filtrar categoría</span><select name="categoria"><option value="">Todas las categorías</option>@foreach($categories as $option)<option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>@endforeach</select></label>
    <button class="sli-button sli-button-primary" type="submit">Buscar</button>
    @if($search || $category)<a class="sli-button sli-button-outline" href="{{ route('abogado.biblioteca') }}">Limpiar</a>@endif
</form>

<div class="sli-library-shelf-heading"><h2>Libros del despacho</h2><span>{{ $documents->total() }} {{ $documents->total() === 1 ? 'libro' : 'libros' }}</span></div>
<div class="sli-library-grid" data-library-catalog data-pdf-assets-url="{{ asset('build/pdfjs') }}/">
@forelse($documents as $document)
    <a class="sli-library-book" href="{{ route('abogado.biblioteca.read', $document) }}" aria-label="Leer y anotar: {{ $document->title }}">
        <div class="sli-library-cover" data-library-cover data-content-url="{{ route('abogado.biblioteca.content', $document) }}" aria-hidden="true">
            <div class="sli-library-cover-placeholder"><span>G.C · BIBLIOTECA LEGAL</span><strong>{{ $document->title }}</strong><span>{{ $document->category }}</span></div>
            <span class="sli-library-cover-action"><span class="material-symbols-outlined">auto_stories</span>Abrir libro</span>
        </div>
        <div class="sli-library-book-info"><div class="sli-library-meta"><span>{{ $document->category }}</span><span>{{ $document->jurisdiction }}</span></div><h3>{{ $document->title }}</h3>@if($document->description)<p>{{ $document->description }}</p>@endif<span class="sli-library-read-label">Leer y anotar <span aria-hidden="true">→</span></span></div>
    </a>
@empty
    <section class="sli-panel sli-empty sli-library-empty"><div class="sli-empty-icon"><span class="material-symbols-outlined" aria-hidden="true">local_library</span></div><h3>{{ $search || $category ? 'No encontramos libros con esos filtros' : 'Tu próxima lectura empieza aquí' }}</h3><p>{{ $search || $category ? 'Prueba con otras palabras o cambia la categoría.' : 'Cuando administración publique un libro PDF, aparecerá su portada aquí. Ábrelo para leer, subrayar y guardar tus notas.' }}</p></section>
@endforelse
</div>
@if($documents->hasPages())<div class="sli-public-pagination">{{ $documents->links() }}</div>@endif
@if($sources->isNotEmpty())
<details class="sli-library-sources"><summary><span>Fuentes oficiales <span class="sli-library-source-count">{{ $sources->count() }}</span></span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary><p>Portales de consulta externos al despacho.</p><div class="sli-library-sources-list">
@foreach($sources as $source)
    <article><div><span class="sli-eyebrow">{{ $source->category }} · {{ $source->jurisdiction }}</span><h3>{{ $source->title }}</h3>@if($source->description)<p>{{ $source->description }}</p>@endif</div><a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer">Consultar fuente oficial <span aria-hidden="true">↗</span></a></article>
@endforeach
</div></details>
@endif
<p class="sli-library-footnote">Tus anotaciones son personales. Verifica siempre la vigencia de la norma antes de usarla en un caso.</p>
@endsection
