@extends('layouts.app')
@section('title', 'Mis Citas')

@section('content')

{{-- Encabezado --}}
<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-2">Portal del Cliente</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Mis Citas</h1>
    <p class="text-outline mt-1 text-sm">Historial de todas tus consultas legales.</p>
</div>

@if($citas->isEmpty())
    {{-- Estado vacío --}}
    <div class="bg-surface-container border border-outline-variant flex flex-col items-center justify-center py-20 gap-4">
        <span class="material-symbols-outlined text-outline" style="font-size:48px;">calendar_month</span>
        <p class="text-on-surface-variant text-sm">No tienes citas registradas aún.</p>
        <a href="{{ route('cliente.nueva-cita') }}"
           class="mt-2 inline-flex items-center gap-2 bg-secondary text-on-secondary text-xs font-grotesk font-bold tracking-widest uppercase px-5 py-2.5 hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[16px]">add</span>
            Agendar ahora
        </a>
    </div>

@else
    {{-- Tarjetas de citas --}}
    <div class="flex flex-col gap-3">
        @foreach($citas as $cita)
        @php
            $estado = $cita->estado;
            $badgeClass = match(true) {
                str_contains($estado, 'confirmad')  => 'bg-[#0a1f12] text-[#4caf82] border-[#1a4d2a]',
                str_contains($estado, 'cancelad')   => 'bg-[#1a0a0a] text-error border-error-container',
                str_contains($estado, 'completad')  => 'bg-surface-container-high text-outline border-outline-variant',
                default                             => 'bg-[#1a1400] text-secondary border-[#3a3000]',
            };
            $iconoEstado = match(true) {
                str_contains($estado, 'confirmad') => 'check_circle',
                str_contains($estado, 'cancelad')  => 'cancel',
                str_contains($estado, 'completad') => 'task_alt',
                default                            => 'schedule',
            };
        @endphp

        <div class="bg-surface-container border border-outline-variant hover:border-outline transition-colors duration-150">
            <div class="flex flex-col md:flex-row md:items-center gap-4 px-6 py-5">

                {{-- Fecha destacada --}}
                <div class="flex-shrink-0 w-14 flex flex-col items-center justify-center bg-surface-container-high border border-outline-variant py-2 px-1">
                    <span class="font-caslon text-2xl text-secondary leading-none">{{ $cita->fecha->format('d') }}</span>
                    <span class="text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline mt-0.5">{{ $cita->fecha->translatedFormat('M') }}</span>
                    <span class="text-[10px] font-grotesk text-outline">{{ $cita->fecha->format('Y') }}</span>
                </div>

                {{-- Info principal --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-2">
                        <code class="text-[11px] font-grotesk font-semibold tracking-widest text-outline">{{ $cita->codigo }}</code>
                        <span class="inline-flex items-center gap-1 border text-[11px] font-grotesk font-semibold uppercase tracking-wider px-2 py-0.5 {{ $badgeClass }}">
                            <span class="material-symbols-outlined text-[12px]">{{ $iconoEstado }}</span>
                            {{ ucfirst(str_replace('_', ' ', $estado)) }}
                        </span>
                    </div>

                    <p class="text-on-surface font-grotesk font-semibold text-sm truncate">{{ $cita->abogado->nombre }}</p>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5">
                        <span class="flex items-center gap-1 text-outline text-xs">
                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                            {{ $cita->hora_inicio }}
                        </span>
                        <span class="flex items-center gap-1 text-outline text-xs">
                            <span class="material-symbols-outlined text-[14px]">gavel</span>
                            {{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}
                        </span>
                        @if($cita->monto)
                        <span class="flex items-center gap-1 text-outline text-xs">
                            <span class="material-symbols-outlined text-[14px]">payments</span>
                            ${{ number_format($cita->monto, 2) }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Acción --}}
                <div class="flex-shrink-0 flex items-center md:justify-end">
                    @if($cita->estaPendiente())
                        <a href="{{ route('pago.crear-sesion', $cita->id) }}"
                           class="inline-flex items-center gap-2 bg-secondary text-on-secondary text-[11px] font-grotesk font-bold tracking-widest uppercase px-4 py-2 hover:opacity-90 transition-opacity">
                            <span class="material-symbols-outlined text-[15px]">credit_card</span>
                            Pagar ahora
                        </a>
                    @elseif($cita->puedeCancelarse())
                        <form method="POST" action="{{ route('cliente.cancelar', $cita->id) }}"
                              onsubmit="return confirm('¿Seguro que deseas cancelar esta cita?')">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 border border-error-container text-error text-[11px] font-grotesk font-bold tracking-widest uppercase px-4 py-2 hover:bg-[#1a0a0a] transition-colors">
                                <span class="material-symbols-outlined text-[15px]">cancel</span>
                                Cancelar
                            </button>
                        </form>
                    @else
                        <span class="text-outline text-xs">—</span>
                    @endif
                </div>

            </div>
        </div>
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($citas->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $citas->links() }}
    </div>
    @endif

@endif

@endsection