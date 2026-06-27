@extends('layouts.app')
@section('title', 'Mi Panel')

@section('content')

<div class="mb-10">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Portal del Abogado</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">{{ auth()->user()->nombre }}</h1>
    <p class="text-outline mt-1 text-sm">{{ auth()->user()->especialidad ?? 'Abogado' }}</p>
</div>

{{-- Citas de hoy --}}
<div class="bg-surface-container border border-outline-variant mb-6">
    <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between">
        <h2 class="font-caslon text-xl text-on-surface">Citas de Hoy</h2>
        <span class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline">
            {{ $citasHoy->count() }} cita(s)
        </span>
    </div>

    @if($citasHoy->isEmpty())
        <div class="px-6 py-10 text-center">
            <span class="material-symbols-outlined text-4xl text-outline">event_available</span>
            <p class="text-outline text-sm mt-3">Sin citas programadas para hoy.</p>
        </div>
    @else
        <div class="divide-y divide-outline-variant">
            @foreach($citasHoy as $cita)
            <div class="px-6 py-4 flex items-center gap-4">
                <div class="bg-secondary text-on-secondary text-center px-3 py-2 flex-shrink-0 min-w-[52px]">
                    <p class="text-[10px] font-grotesk font-bold tracking-widest uppercase">HOY</p>
                    <p class="font-caslon text-xl font-bold leading-none">{{ $cita->hora_inicio }}</p>
                </div>
                <div class="flex-1">
                    <p class="text-[11px] font-grotesk font-bold tracking-[.1em] uppercase text-on-surface">
                        {{ str_replace('_', ' ', strtoupper($cita->tipo)) }}
                    </p>
                    <p class="text-sm text-outline mt-0.5">
                        <span class="material-symbols-outlined text-[14px]">person</span>
                        {{ $cita->cliente->nombre }}
                        &nbsp;·&nbsp;{{ ucfirst($cita->modalidad) }}
                    </p>
                </div>
                <span class="text-[11px] font-grotesk font-bold tracking-widest uppercase border border-outline-variant px-3 py-1 text-on-surface-variant">
                    {{ ucfirst($cita->modalidad) }}
                </span>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Próximas citas --}}
<div class="bg-surface-container border border-outline-variant">
    <div class="px-6 py-4 border-b border-outline-variant">
        <h2 class="font-caslon text-xl text-on-surface">Próximas Citas</h2>
    </div>

    @if($proximasCitas->isEmpty())
        <div class="px-6 py-10 text-center">
            <p class="text-outline text-sm">No tienes citas programadas próximamente.</p>
        </div>
    @else
        <div class="divide-y divide-outline-variant">
            @foreach($proximasCitas as $cita)
            <div class="px-6 py-4 flex items-center gap-4">
                <div class="w-12 text-center flex-shrink-0">
                    <p class="text-[10px] font-grotesk font-bold tracking-widest uppercase text-outline">{{ $cita->fecha->format('M') }}</p>
                    <p class="font-caslon text-2xl text-on-surface leading-none">{{ $cita->fecha->format('d') }}</p>
                </div>
                <div class="w-px h-8 bg-outline-variant flex-shrink-0"></div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-on-surface">{{ $cita->cliente->nombre }}</p>
                    <p class="text-[12px] text-outline mt-0.5">{{ $cita->hora_inicio }} · {{ str_replace('_', ' ', ucfirst($cita->tipo)) }}</p>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<div class="mt-6">
    <a href="{{ route('abogado.agenda') }}"
       class="inline-block bg-secondary text-on-secondary font-grotesk text-[13px] font-bold tracking-[.1em] uppercase px-8 py-4 hover:opacity-90 transition-opacity">
        VER AGENDA COMPLETA
    </a>
</div>
@endsection
