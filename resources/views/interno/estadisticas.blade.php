@extends('layouts.app')
@section('title', 'Estadísticas')

@section('content')

<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Gestión Interna</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Estadísticas</h1>
    <p class="text-outline mt-1 text-sm">Ingresos de los últimos 6 meses.</p>
</div>

<div class="bg-surface-container border border-outline-variant p-6">
    @if($ingresosMes->isEmpty())
        <p class="text-outline text-sm text-center py-12">Sin datos de ingresos aún.</p>
    @else
        @php
            $meses = ['', 'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            $max   = $ingresosMes->max('total') ?: 1;
        @endphp
        <div class="flex items-end gap-3 h-48">
            @foreach($ingresosMes as $fila)
            @php $altura = round(($fila->total / $max) * 100); @endphp
            <div class="flex flex-col items-center gap-2 flex-1">
                <p class="text-[11px] font-grotesk font-bold text-secondary">${{ number_format($fila->total, 0) }}</p>
                <div class="w-full bg-secondary" style="height: {{ $altura }}%; min-height: 4px;"></div>
                <p class="text-[11px] font-grotesk font-semibold uppercase tracking-wider text-outline">
                    {{ $meses[$fila->mes] }}
                </p>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
