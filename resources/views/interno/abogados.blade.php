@extends('layouts.app')
@section('title', 'Abogados')

@section('content')

<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Gestión Interna</p>
        <h1 class="font-caslon text-4xl font-normal text-on-surface">Abogados</h1>
    </div>
    <button onclick="document.getElementById('modal-abogado').classList.remove('hidden')"
        class="bg-secondary text-on-secondary font-grotesk text-[13px] font-bold tracking-[.1em] uppercase px-6 py-3 hover:opacity-90 transition-opacity">
        + REGISTRAR ABOGADO
    </button>
</div>

{{-- Tabla --}}
<div class="bg-surface-container border border-outline-variant">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant bg-surface-container-lowest">
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Nombre</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Especialidad</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Email</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Citas</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Estado</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($abogados as $ab)
                <tr class="hover:bg-surface-container-high transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-surface-container-high flex items-center justify-center text-on-surface-variant text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($ab->nombre, 0, 1)) }}
                            </div>
                            <span class="text-on-surface font-medium">{{ $ab->nombre }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $ab->especialidad ?? '—' }}</td>
                    <td class="px-6 py-4 text-on-surface-variant text-[12px]">{{ $ab->email }}</td>
                    <td class="px-6 py-4 text-on-surface">{{ $ab->total_citas }}</td>
                    <td class="px-6 py-4">
                        @if($ab->activo)
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#0a1a0f] text-[#4caf82] border border-[#1a4d2a]">ACTIVO</span>
                        @else
                            <span class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase px-2.5 py-1 bg-[#1a0a0a] text-error border border-error-container">INACTIVO</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('interno.abogados.toggle', $ab->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="text-[11px] font-grotesk font-semibold tracking-[.08em] uppercase border border-outline-variant text-on-surface-variant hover:border-secondary hover:text-secondary px-3 py-1.5 transition-colors">
                                {{ $ab->activo ? 'DESACTIVAR' : 'ACTIVAR' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-outline text-sm">
                        No hay abogados registrados aún.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal registro de abogado --}}
<div id="modal-abogado" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="bg-surface-container-low border border-outline-variant w-full max-w-lg">
        <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between">
            <h2 class="font-caslon text-xl text-on-surface">Registrar Abogado</h2>
            <button onclick="document.getElementById('modal-abogado').classList.add('hidden')"
                class="text-outline hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('interno.abogados.crear') }}" class="p-6 flex flex-col gap-5">
            @csrf
            <div>
                <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">Nombre Completo</label>
                <input type="text" name="nombre" required placeholder="Dr. Juan Pérez"
                    class="w-full bg-transparent border-b border-outline-variant text-on-surface font-grotesk text-sm py-2 px-0 focus:outline-none focus:border-secondary transition-colors placeholder-outline">
            </div>
            <div>
                <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">Correo Electrónico</label>
                <input type="email" name="email" required placeholder="abogado@firma.com"
                    class="w-full bg-transparent border-b border-outline-variant text-on-surface font-grotesk text-sm py-2 px-0 focus:outline-none focus:border-secondary transition-colors placeholder-outline">
            </div>
            <div>
                <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">Especialidad</label>
                <input type="text" name="especialidad" required placeholder="Derecho Familiar"
                    class="w-full bg-transparent border-b border-outline-variant text-on-surface font-grotesk text-sm py-2 px-0 focus:outline-none focus:border-secondary transition-colors placeholder-outline">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">Contraseña Inicial</label>
                    <input type="password" name="password" required placeholder="Mín. 8 caracteres"
                        class="w-full bg-transparent border-b border-outline-variant text-on-surface font-grotesk text-sm py-2 px-0 focus:outline-none focus:border-secondary transition-colors placeholder-outline">
                </div>
                <div>
                    <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">WhatsApp</label>
                    <input type="tel" name="telefono_whatsapp" placeholder="+503..."
                        class="w-full bg-transparent border-b border-outline-variant text-on-surface font-grotesk text-sm py-2 px-0 focus:outline-none focus:border-secondary transition-colors placeholder-outline">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-secondary text-on-secondary font-grotesk text-[13px] font-bold tracking-[.1em] uppercase py-3 hover:opacity-90 transition-opacity">
                    REGISTRAR
                </button>
                <button type="button" onclick="document.getElementById('modal-abogado').classList.add('hidden')"
                    class="border border-outline-variant text-on-surface-variant font-grotesk text-[13px] font-bold tracking-[.1em] uppercase px-6 py-3 hover:border-secondary hover:text-secondary transition-colors">
                    CANCELAR
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
