@php
    $navigation = auth()->user()->esAdmin()
        ? [
            ['interno.dashboard', 'Dashboard', 'dashboard'],
            ['interno.abogados', 'Abogados', 'gavel'],
            ['interno.clientes', 'Clientes', 'group'],
            ['interno.citas', 'Todas las citas', 'calendar_month'],
            ['interno.estadisticas', 'Estadísticas', 'bar_chart'],
        ]
        : (auth()->user()->esAbogado()
            ? [
                ['abogado.dashboard', 'Dashboard', 'dashboard'],
                ['abogado.agenda', 'Mi agenda', 'calendar_month'],
            ]
            : [
                ['cliente.dashboard', 'Dashboard', 'dashboard'],
                ['cliente.nueva-cita', 'Nueva cita', 'add_circle'],
                ['cliente.mis-citas', 'Mis citas', 'calendar_month'],
            ]);
@endphp

@foreach($navigation as [$route, $label, $icon])
    <a href="{{ route($route) }}" class="app-nav-link" @if(request()->routeIs($route)) aria-current="page" @endif>
        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $label }}</span>
    </a>
@endforeach
