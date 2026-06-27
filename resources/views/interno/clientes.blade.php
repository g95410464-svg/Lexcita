@extends('layouts.app')
@section('title', 'Clientes')

@section('content')

<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Gestión Interna</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Clientes</h1>
</div>

<div class="bg-surface-container border border-outline-variant">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant bg-surface-container-lowest">
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Nombre</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Email</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">WhatsApp</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Total Citas</th>
                    <th class="text-left px-6 py-3 text-[11px] font-grotesk font-semibold tracking-[.1em] uppercase text-outline">Registrado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($clientes as $cliente)
                <tr class="hover:bg-surface-container-high transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-secondary/10 border border-secondary/30 flex items-center justify-center text-secondary text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                            </div>
                            <span class="text-on-surface font-medium">{{ $cliente->nombre }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-on-surface-variant text-[12px]">{{ $cliente->email }}</td>
                    <td class="px-6 py-4 text-on-surface-variant text-[12px]">{{ $cliente->telefono_whatsapp ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="font-caslon text-xl text-on-surface">{{ $cliente->total_citas }}</span>
                    </td>
                    <td class="px-6 py-4 text-on-surface-variant text-[12px]">{{ $cliente->created_at->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-outline text-sm">Sin clientes registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-outline-variant">
        {{ $clientes->links() }}
    </div>
</div>
@endsection