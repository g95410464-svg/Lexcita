@extends('sli.public.layout')
@section('title', 'Nuestros abogados')
@section('public-content')
<section class="sli-public-cover"><div class="sli-landing-width"><p class="sli-landing-eyebrow">PERSONAS QUE TE ACOMPAÑAN</p><h1>Nuestros abogados</h1><p>Conoce su especialidad y elige con quién dar el siguiente paso.</p></div></section>
<section class="sli-public-directory sli-landing-width" aria-label="Abogados del despacho">
<div class="sli-public-grid">
@forelse($abogados as $abogado)
<article class="sli-lawyer-card">
    <div class="sli-lawyer-monogram" aria-hidden="true">{{ mb_substr($abogado->nombre, 0, 1) }}</div>
    <p class="sli-landing-eyebrow">SERVICIOS LEGALES INTEGRADOS</p>
    <h2>{{ $abogado->nombre }}</h2><p>{{ $abogado->especialidad ?: 'Consulta al despacho sobre su área de atención.' }}</p>
    <a class="sli-button sli-button-outline" href="{{ route('sli.profile', $abogado->id) }}">Conocer perfil <span aria-hidden="true">↗</span></a>
</article>
@empty
<div class="sli-public-empty"><span class="material-symbols-outlined" aria-hidden="true">balance</span><h2>Pronto conocerás a nuestro equipo</h2><p>Cuando el despacho tenga abogados activos registrados, sus perfiles aparecerán aquí.</p><a class="sli-button sli-button-outline" href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contactar al despacho ↗</a></div>
@endforelse
</div>
@if($abogados->hasPages())<nav class="sli-public-pagination" aria-label="Páginas de abogados">@if($abogados->previousPageUrl())<a href="{{ $abogados->previousPageUrl() }}">← Anterior</a>@endif<span>Página {{ $abogados->currentPage() }} de {{ $abogados->lastPage() }}</span>@if($abogados->nextPageUrl())<a href="{{ $abogados->nextPageUrl() }}">Siguiente →</a>@endif</nav>@endif
</section>
@endsection
