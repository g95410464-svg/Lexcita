@extends('layouts.app')
@section('title', 'Todas las Citas')

@section('content')

<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Gestión Interna</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Todas las Citas</h1>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('interno.citas') }}" class="bg-surface-container border border-outline-variant px-6 py-4 flex flex-wrap gap-4 mb-6 items-end">
    <div>
        <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Estado</label>
        <select name="estado"
            class="bg-surface-container-high border border-outline-variant text-on-surface font-grotesk text-sm px-3 py-2 focus:outline-none focus:border-secondary transition-colors">
            <option value="">Todos</option>
            <option value="confirmada"    {{ request('estado') === 'confirmada'    ? 'selected' : '' }}>Confirmada</option>
            <option value="pendiente_pago"{{ request('estado') === 'pendiente_pago'? 'selected' : '' }}>Pendiente de Pago</option>
            <option value="cancelada"     {{ request('estado') === 'cancelada'     ? 'selected' : '' }}>Cancelada</option>
        </select>
    </div>
    <div>
        <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Abogado</label>
        <select name="abogado_id"
            class="bg-surface-container-high border border-outline-variant text-on-surface font-grotesk text-sm px-3 py-2 focus:outline-none focus:border-secondary transition-colors">
            <option value="">Todos</option>
            @foreach($abogados as $ab)
                <option value="{{ $ab->id }}" {{ request('abogado_id') == $ab->id ? 'selected' : '' }}>
                    {{ $ab->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="flex-1 min-w-[200px]">
        <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-outline mb-2">Buscar</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Código o nombre de cliente..."
            class="w-full bg-surface-container-high border border-outline-variant text-on-surface font-grotesk text-sm px-3 py-2 focus:outline-none focus:border-secondary transition-colors placeholder-outline">
    </div>
    <button type="submit"
        class="bg-secondary text-on-secondary font-grotesk text-[13px] font-bold tracking-[.1em] uppercase px-6 py-2 hover:opacity-90 transition-opacity">
        FILTRAR
    </button>
    @if(request()->anyFilled(['estado','abogado_id','buscar']))
    <a href="{{ route('interno.citas') }}"
       class="border border-outline-variant text-on-surface-variant font-grotesk text-[13px] font-bold tracking-[.1em] uppercase px-6 py-2 hover:border-secondary hover:text-secondary transition-colors">
        LIMPIAR
    </a>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-surface-container border border-outline-variant">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant bg-surface-container-lowest">
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Código</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Cliente</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Abogado</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Fecha</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Hora</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Monto</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($citas as $cita)
                <tr class="hover:bg-surface-container-high transition-colors">
                    <td class="px-6 py-4 font-mono text-[12px] text-secondary">{{ $cita->codigo }}</td>
                    <td class="px-6 py-4 text-on-surface">{{ $cita->cliente->nombre }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $cita->abogado->nombre }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $cita->fecha->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $cita->hora_inicio }}</td>
                    <td class="px-6 py-4 text-on-surface">${{ number_format($cita->monto, 2) }}</td>
                    <td class="px-6 py-4">
                        @if($cita->estado === 'confirmada')
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#0a1a0f] text-[#4caf82] border border-[#1a4d2a]">CONFIRMADA</span>
                        @elseif($cita->estado === 'cancelada')
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#1a0a0a] text-error border border-error-container">CANCELADA</span>
                        @else
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#1a1500] text-secondary border border-[#3c2f00]">PENDIENTE</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-outline text-sm">
                        No se encontraron citas con esos filtros.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-outline-variant">
        {{ $citas->appends(request()->query())->links() }}
    </div>
</div>
@endsection
