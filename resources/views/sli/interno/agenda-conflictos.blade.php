@extends('layouts.app')
@section('title','Conflictos de agenda')
@section('content')
<div class="sli-page-heading"><div><span class="sli-eyebrow">SEGUIMIENTO DEL DESPACHO</span><h1>Conflictos de agenda</h1><p>Coordina una solución con el abogado y el cliente. El cambio de cita requiere la aceptación del cliente.</p></div></div>
@forelse($conflicts as $conflict)<article class="sli-agenda-section"><span class="sli-eyebrow">{{ str_replace('_',' ',$conflict->status) }}</span><h2>{{ $conflict->cita->codigo }} · {{ $conflict->cita->fecha->format('d/m/Y') }} · {{ substr($conflict->cita->hora_inicio,0,5) }}</h2><p>{{ $conflict->cita->abogado->nombre }} · {{ $conflict->cita->cliente->nombre }}</p><p class="sli-agenda-help">{{ $conflict->reason }}</p></article>
@empty<section class="sli-panel sli-empty"><h2>No hay conflictos pendientes</h2></section>@endforelse
{{ $conflicts->links() }}
@endsection
