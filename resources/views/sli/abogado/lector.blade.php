@extends('layouts.app')
@section('title', $document->title)
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">BIBLIOTECA COMPARTIDA · {{ $document->category }}</span><h1>{{ $document->title }}</h1><p>El libro es compartido por el equipo; tus subrayados, notas y marcadores son personales.</p></div><a class="sli-button sli-button-outline" href="{{ route('abogado.biblioteca') }}">← Biblioteca</a></div>

<section class="sli-reader" data-library-reader
    data-content-url="{{ route('abogado.biblioteca.content', $document) }}"
    data-annotations-url="{{ route('abogado.biblioteca.annotations', $document) }}"
    data-pdf-assets-url="{{ asset('build/pdfjs') }}/">
    <div class="sli-reader-toolbar" aria-label="Controles de lectura">
        <button type="button" data-reader-prev disabled aria-label="Página anterior">←</button>
        <label>Página <input data-reader-page type="number" min="1" value="1" disabled></label><span data-reader-pages>de —</span>
        <button type="button" data-reader-next disabled aria-label="Página siguiente">→</button>
        <label>Zoom <select data-reader-zoom disabled><option value="1">Ajustar al ancho</option><option value="1.25">125 %</option><option value="1.5">150 %</option><option value="2">200 %</option></select></label>
        <button type="button" data-reader-bookmark disabled>Marcar página</button>
    </div>
    <p class="sli-reader-status" data-reader-status role="status" aria-live="polite">Abriendo libro…</p>
    <div class="sli-reader-layout">
        <div class="sli-reader-scroll" data-reader-scroll tabindex="0" aria-label="Documento PDF"><div class="sli-reader-page" data-reader-sheet></div></div>
        <aside class="sli-reader-notes" aria-label="Mis anotaciones">
            <h2>Mi espacio de lectura</h2>
            <p>Selecciona texto en la página y elige cómo marcarlo. En documentos escaneados sin texto seleccionable puedes guardar marcadores y notas de página.</p>
            <p class="sli-reader-selection" data-reader-selection>Ningún texto seleccionado.</p>
            <label>Nota opcional<textarea data-reader-note maxlength="2000" rows="3" placeholder="Escribe tu comentario…"></textarea></label>
            <div class="sli-reader-mark-actions"><button type="button" data-reader-mark="highlight" disabled>Resaltar</button><button type="button" data-reader-mark="underline" disabled>Subrayar</button></div>
            <h3>Mis notas y marcadores</h3>
            <div data-reader-notes></div>
        </aside>
    </div>
</section>
<noscript><p>Activa JavaScript para usar el lector.</p></noscript>
@endsection
