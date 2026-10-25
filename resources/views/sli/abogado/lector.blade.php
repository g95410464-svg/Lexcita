@extends('layouts.app')
@section('title', $document->title)
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">BIBLIOTECA COMPARTIDA · {{ $document->category }}</span><h1>{{ $document->title }}</h1><p>El libro es compartido por el equipo; tus subrayados, notas y marcadores son personales.</p></div><a class="sli-button sli-button-outline" href="{{ route('abogado.biblioteca') }}">← Biblioteca</a></div>

<section class="sli-reader" data-library-reader
    data-content-url="{{ route('abogado.biblioteca.content', $document) }}"
    data-document-id="{{ $document->id }}" data-reader-user="{{ auth()->id() }}"
    data-annotations-url="{{ route('abogado.biblioteca.annotations', $document) }}"
    data-pdf-assets-url="{{ asset('build/pdfjs') }}/">
    <div class="sli-reader-toolbar" aria-label="Controles de lectura">
        <span class="sli-reader-toolbar-title"><span class="material-symbols-outlined" aria-hidden="true">auto_stories</span>Lector legal</span>
        <button type="button" data-reader-prev disabled aria-label="Página anterior">←</button>
        <label>Página <input data-reader-page type="number" min="1" value="1" disabled></label><span data-reader-pages>de —</span>
        <button type="button" data-reader-next disabled aria-label="Página siguiente">→</button>
        <label>Zoom <select data-reader-zoom disabled><option value="1">Ajustar al ancho</option><option value="1.25">125 %</option><option value="1.5">150 %</option><option value="2">200 %</option></select></label>
        <button type="button" data-reader-bookmark disabled>Marcar página</button>
        <button type="button" data-reader-focus aria-pressed="false">Ocultar notas</button>
    </div>
    <div class="sli-reader-progress" role="progressbar" aria-label="Progreso de lectura" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-reader-progress><span></span></div>
    <p class="sli-reader-status" data-reader-status role="status" aria-live="polite">Abriendo libro…</p>
    <div class="sli-reader-layout">
        <div class="sli-reader-scroll" data-reader-scroll tabindex="0" aria-label="Documento PDF"><div class="sli-reader-page" data-reader-sheet></div></div>
        <aside class="sli-reader-notes" aria-label="Mis anotaciones">
            <span class="sli-eyebrow">SOLO PARA TI</span><h2>Mi cuaderno</h2>
            <details class="sli-reader-guide"><summary>Cómo anotar este libro</summary><p>Selecciona texto y elige resaltar o subrayar. Puedes añadir un comentario antes de guardar. En páginas escaneadas, escribe una nota y usa Marcar página.</p></details>
            <p class="sli-reader-selection" data-reader-selection>Ningún texto seleccionado.</p>
            <label>Nota opcional<textarea data-reader-note maxlength="2000" rows="3" placeholder="Escribe tu comentario…"></textarea></label>
            <div class="sli-reader-mark-actions"><button type="button" data-reader-mark="highlight" disabled>Resaltar</button><button type="button" data-reader-mark="underline" disabled>Subrayar</button></div>
            <h3>Notas y marcadores <span data-reader-note-count>0</span></h3>
            <div data-reader-notes></div>
        </aside>
    </div>
</section>
<noscript><p>Activa JavaScript para usar el lector.</p></noscript>
@endsection
