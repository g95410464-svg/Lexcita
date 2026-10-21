@extends('layouts.app')
@section('title', 'Biblioteca legal')
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">RECURSOS PARA TU PRÁCTICA</span><h1>Biblioteca legal</h1><p>Consulta legislación y material jurídico desde fuentes disponibles en línea.</p></div></div>

<form class="sli-library-filters" action="{{ route('abogado.biblioteca') }}" method="GET">
    <label class="sli-library-search"><span class="material-symbols-outlined" aria-hidden="true">search</span><span class="sr-only">Buscar en la biblioteca</span><input name="q" value="{{ $search }}" placeholder="Buscar por título, tema o jurisdicción" maxlength="120"></label>
    <label class="sli-library-category"><span class="sr-only">Filtrar categoría</span><select name="categoria"><option value="">Todas las categorías</option>@foreach($categories as $option)<option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>@endforeach</select></label>
    <button class="sli-button sli-button-primary" type="submit">Buscar</button>
    @if($search || $category)<a class="sli-button sli-button-outline" href="{{ route('abogado.biblioteca') }}">Limpiar</a>@endif
</form>

<div class="sli-library-grid">
@forelse($documents as $document)
    <article class="sli-library-card">
        <div class="sli-library-card-icon"><span class="material-symbols-outlined" aria-hidden="true">{{ $document->storage_path ? 'picture_as_pdf' : 'menu_book' }}</span></div>
        <div class="sli-library-card-content"><div class="sli-library-meta"><span>{{ $document->category }}</span><span>{{ $document->jurisdiction }}</span></div><h2>{{ $document->title }}</h2>@if($document->description)<p>{{ $document->description }}</p>@endif</div>
        @if($document->storage_path)<a class="sli-button sli-button-outline" href="{{ route('abogado.biblioteca.download', $document) }}"><span class="material-symbols-outlined" aria-hidden="true">download</span>Descargar PDF</a>@endif
        @if($document->source_url)<a class="sli-library-source" href="{{ $document->source_url }}" target="_blank" rel="noopener noreferrer">Consultar fuente oficial <span aria-hidden="true">↗</span></a>@endif
    </article>
@empty
    <section class="sli-panel sli-empty sli-library-empty"><div class="sli-empty-icon"><span class="material-symbols-outlined" aria-hidden="true">local_library</span></div><h2>No encontramos recursos</h2><p>Prueba con otras palabras o revisa nuevamente la biblioteca más adelante.</p></section>
@endforelse
</div>
@if($documents->hasPages())<div class="sli-public-pagination">{{ $documents->links() }}</div>@endif
<p class="sli-library-footnote">Los enlaces abren las publicaciones de su institución de origen. Verifica siempre la vigencia de la norma antes de usarla en un caso.</p>
@endsection
