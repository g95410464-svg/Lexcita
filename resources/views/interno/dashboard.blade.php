@extends('layouts.app')
@section('title', 'Panel Interno')

@section('content')

<div class="mb-10">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Gestión Interna</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Panel de Control</h1>
    <p class="text-outline mt-1 text-sm">Visión global del sistema LexCita.</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-10">
    <div class="bg-surface-container border border-outline-variant p-5">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Citas Totales</p>
        <p class="font-caslon text-4xl text-on-surface">{{ $stats['total_citas'] }}</p>
    </div>
    <div class="bg-surface-container border border-outline-variant p-5">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Citas Hoy</p>
        <p class="font-caslon text-4xl text-on-surface">{{ $stats['citas_hoy'] }}</p>
    </div>
    <div class="bg-surface-container border border-outline-variant p-5">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Confirmadas</p>
        <p class="font-caslon text-4xl text-on-surface">{{ $stats['confirmadas'] }}</p>
    </div>
    <div class="bg-surface-container border border-outline-variant p-5">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Ingresos Totales</p>
        <p class="font-caslon text-4xl text-secondary">${{ number_format($stats['ingresos'], 2) }}</p>
    </div>
    <div class="bg-surface-container border border-outline-variant p-5">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Clientes</p>
        <p class="font-caslon text-4xl text-on-surface">{{ $stats['total_clientes'] }}</p>
    </div>
    <div class="bg-surface-container border border-outline-variant p-5">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Abogados</p>
        <p class="font-caslon text-4xl text-on-surface">{{ $stats['total_abogados'] }}</p>
    </div>
</div>

{{-- Citas recientes --}}
<div class="bg-surface-container border border-outline-variant">
    <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between">
        <h2 class="font-caslon text-xl text-on-surface">Citas Recientes</h2>
        <a href="{{ route('interno.citas') }}"
           class="text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-secondary hover:underline">
            Ver todas
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant bg-surface-container-lowest">
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Código</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Cliente</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Abogado</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Fecha</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @foreach($citasRecientes as $cita)
                <tr class="hover:bg-surface-container-high transition-colors">
                    <td class="px-6 py-4 font-mono text-[12px] text-secondary">{{ $cita->codigo }}</td>
                    <td class="px-6 py-4 text-on-surface">{{ $cita->cliente->nombre }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $cita->abogado->nombre }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $cita->fecha->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">
                        @if($cita->estado === 'confirmada')
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#0a1a0f] text-[#4caf82] border border-[#1a4d2a]">CONFIRMADA</span>
                        @elseif($cita->estado === 'cancelada')
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#1a0a0a] text-error border border-error-container">CANCELADA</span>
                        @else
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#1a1500] text-secondary border border-[#3c2f00]">PENDIENTE PAGO</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
