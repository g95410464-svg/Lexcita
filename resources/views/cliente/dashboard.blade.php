@extends('layouts.app')
@section('title', 'Mi Portal')

@section('content')

{{-- ── Encabezado ─────────────────────────────────────────── --}}
<div class="mb-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div>
        <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Portal del Cliente</p>
        <h1 class="font-caslon text-4xl font-normal text-on-surface leading-tight">
            Bienvenido, {{ explode(' ', auth()->user()->nombre)[0] }}
        </h1>
        <p class="text-outline mt-2 text-sm">
            {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        </p>
    </div>
    <a href="{{ route('cliente.nueva-cita') }}"
       class="inline-flex items-center gap-2 bg-secondary text-on-secondary font-grotesk text-[11px]
              font-bold tracking-widest uppercase px-5 py-3 hover:opacity-90 transition-opacity self-start sm:self-auto flex-shrink-0">
        <span class="material-symbols-outlined text-[16px]">add</span>
        Nueva Cita
    </a>
</div>

{{-- ── Stats ───────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
    @php
        $pendientes  = auth()->user()->citasComoCliente()->where('estado','pendiente_pago')->count();
        $completadas = auth()->user()->citasComoCliente()->where('estado','completada')->count();
    @endphp

    <div class="bg-surface-container border border-outline-variant p-5 flex flex-col gap-3">
        <span class="material-symbols-outlined text-outline text-[20px]">calendar_month</span>
        <div>
            <p class="font-caslon text-3xl text-secondary">{{ $totalCitas }}</p>
            <p class="text-[11px] font-grotesk font-semibold tracking-widest uppercase text-outline mt-1">Total Citas</p>
        </div>
    </div>

    <div class="bg-surface-container border border-outline-variant p-5 flex flex-col gap-3">
        <span class="material-symbols-outlined text-outline text-[20px]">event_upcoming</span>
        <div>
            <p class="font-caslon text-3xl text-on-surface">{{ $proximasCitas->count() }}</p>
            <p class="text-[11px] font-grotesk font-semibold tracking-widest uppercase text-outline mt-1">Próximas</p>
        </div>
    </div>

    <div class="bg-surface-container border border-outline-variant p-5 flex flex-col gap-3">
        <span class="material-symbols-outlined text-outline text-[20px]">schedule</span>
        <div>
            <p class="font-caslon text-3xl {{ $pendientes > 0 ? 'text-secondary' : 'text-on-surface' }}">{{ $pendientes }}</p>
            <p class="text-[11px] font-grotesk font-semibold tracking-widest uppercase text-outline mt-1">Pend. Pago</p>
        </div>
    </div>

    <div class="bg-surface-container border border-outline-variant p-5 flex flex-col gap-3">
        <span class="material-symbols-outlined text-outline text-[20px]">task_alt</span>
        <div>
            <p class="font-caslon text-3xl text-on-surface">{{ $completadas }}</p>
            <p class="text-[11px] font-grotesk font-semibold tracking-widest uppercase text-outline mt-1">Completadas</p>
        </div>
    </div>
</div>

{{-- ── Citas pendientes de pago (alerta) ──────────────────── --}}
@php $citasPendPago = auth()->user()->citasComoCliente()->where('estado','pendiente_pago')->with('abogado')->orderBy('fecha')->get(); @endphp
@if($citasPendPago->isNotEmpty())
<div class="bg-[#1a1400] border border-[#3a3000] p-4 mb-6 flex items-start gap-3">
    <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">warning</span>
    <div class="flex-1 min-w-0">
        <p class="text-secondary text-sm font-grotesk font-semibold mb-1">
            {{ $citasPendPago->count() }} cita(s) pendiente(s) de pago
        </p>
        <div class="flex flex-wrap gap-2 mt-2">
            @foreach($citasPendPago as $cp)
            <a href="{{ route('pago.crear-sesion', $cp->id) }}"
               class="inline-flex items-center gap-1.5 bg-secondary text-on-secondary text-[11px]
                      font-grotesk font-bold tracking-widest uppercase px-3 py-1.5 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[13px]">credit_card</span>
                Pagar — {{ $cp->fecha->format('d/m') }} {{ $cp->hora_inicio }}
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Próximas citas confirmadas ──────────────────────────── --}}
@if($proximasCitas->isNotEmpty())
<div class="bg-surface-container border border-outline-variant mb-8">
    <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between">
        <h2 class="font-caslon text-xl text-on-surface">Próximas Citas</h2>
        <span class="text-[11px] font-grotesk font-semibold tracking-widest uppercase text-outline">
            {{ $proximasCitas->count() }} confirmada(s)
        </span>
    </div>
    <div class="divide-y divide-outline-variant">
        @foreach($proximasCitas as $cita)
        @php
            $diasRestantes = now()->startOfDay()->diffInDays($cita->fecha->startOfDay(), false);
        @endphp
        <div class="px-6 py-5 flex items-center gap-4">
            {{-- Bloque fecha --}}
            <div class="bg-secondary text-on-secondary text-center px-3 py-2 flex-shrink-0 w-14">
                <p class="text-[9px] font-grotesk font-bold tracking-widest uppercase">
                    {{ $cita->fecha->isoFormat('MMM') }}
                </p>
                <p class="font-caslon text-2xl font-bold leading-none">{{ $cita->fecha->format('d') }}</p>
            </div>

            {{-- Información --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <p class="text-on-surface font-grotesk font-semibold text-sm">
                        {{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}
                    </p>
                    @if($diasRestantes === 0)
                        <span class="bg-[#0a1f12] text-[#4caf82] border border-[#1a4d2a] text-[10px] font-grotesk font-bold uppercase tracking-wider px-2 py-0.5">HOY</span>
                    @elseif($diasRestantes === 1)
                        <span class="bg-[#1a1400] text-secondary border border-[#3a3000] text-[10px] font-grotesk font-bold uppercase tracking-wider px-2 py-0.5">MAÑANA</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="flex items-center gap-1 text-outline text-xs">
                        <span class="material-symbols-outlined text-[13px]">person</span>
                        {{ $cita->abogado->nombre }}
                    </span>
                    <span class="flex items-center gap-1 text-outline text-xs">
                        <span class="material-symbols-outlined text-[13px]">schedule</span>
                        {{ $cita->hora_inicio }}
                    </span>
                    <span class="flex items-center gap-1 text-outline text-xs">
                        <span class="material-symbols-outlined text-[13px]">
                            {{ $cita->modalidad === 'virtual' ? 'videocam' : 'location_on' }}
                        </span>
                        {{ ucfirst($cita->modalidad) }}
                    </span>
                </div>
            </div>

            {{-- Días restantes --}}
            <div class="flex-shrink-0 text-right hidden sm:block">
                @if($diasRestantes > 1)
                <p class="font-caslon text-2xl text-on-surface leading-none">{{ $diasRestantes }}</p>
                <p class="text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline mt-0.5">días</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    <div class="px-6 py-3 border-t border-outline-variant">
        <a href="{{ route('cliente.mis-citas') }}"
           class="inline-flex items-center gap-1.5 text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline hover:text-secondary transition-colors">
            Ver todas mis citas
            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
    </div>
</div>

@else
{{-- Sin próximas citas --}}
<div class="bg-surface-container border border-outline-variant flex flex-col items-center justify-center py-16 gap-4 mb-8">
    <span class="material-symbols-outlined text-outline" style="font-size:44px;">event_available</span>
    <p class="text-on-surface-variant text-sm font-grotesk">No tienes citas confirmadas próximas.</p>
    <a href="{{ route('cliente.nueva-cita') }}"
       class="inline-flex items-center gap-2 bg-secondary text-on-secondary text-[11px] font-grotesk
              font-bold tracking-widest uppercase px-5 py-2.5 hover:opacity-90 transition-opacity">
        <span class="material-symbols-outlined text-[15px]">add</span>
        Agendar ahora
    </a>
</div>
@endif

{{-- ── Accesos rápidos ─────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <a href="{{ route('cliente.nueva-cita') }}"
       class="bg-surface-container border border-outline-variant hover:border-secondary group p-5
              flex items-center gap-4 transition-colors duration-150">
        <span class="material-symbols-outlined text-outline group-hover:text-secondary transition-colors text-[24px]">add_circle</span>
        <div>
            <p class="text-on-surface font-grotesk font-semibold text-sm">Nueva Cita</p>
            <p class="text-outline text-xs mt-0.5">Agenda una consulta</p>
        </div>
    </a>

    <a href="{{ route('cliente.mis-citas') }}"
       class="bg-surface-container border border-outline-variant hover:border-secondary group p-5
              flex items-center gap-4 transition-colors duration-150">
        <span class="material-symbols-outlined text-outline group-hover:text-secondary transition-colors text-[24px]">calendar_month</span>
        <div>
            <p class="text-on-surface font-grotesk font-semibold text-sm">Mis Citas</p>
            <p class="text-outline text-xs mt-0.5">Ver historial completo</p>
        </div>
    </a>

    <div class="bg-surface-container border border-outline-variant p-5 flex items-center gap-4">
        <span class="material-symbols-outlined text-outline text-[24px]">support_agent</span>
        <div>
            <p class="text-on-surface font-grotesk font-semibold text-sm">Soporte</p>
            <p class="text-outline text-xs mt-0.5">Laureti & Associates</p>
        </div>
    </div>
</div>

@endsection