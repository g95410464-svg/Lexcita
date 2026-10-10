@php
    $navigation = auth()->user()->esAdmin()
        ? [['interno.dashboard', 'Resumen', 'space_dashboard'], ['interno.citas', 'Citas', 'calendar_month'], ['interno.abogados', 'Equipo legal', 'balance'], ['interno.clientes', 'Clientes', 'group'], ['interno.estadisticas', 'Estadísticas', 'bar_chart']]
        : (auth()->user()->esAbogado()
            ? [['abogado.dashboard', 'Mi resumen', 'space_dashboard'], ['abogado.agenda', 'Mi agenda', 'calendar_month']]
            : [['cliente.dashboard', 'Mi resumen', 'space_dashboard'], ['cliente.nueva-cita', 'Agendar consulta', 'add_circle'], ['cliente.mis-citas', 'Mis citas', 'calendar_month']]);
@endphp
@foreach($navigation as [$route, $label, $icon])
    <div class="sli-nav-item"><a href="{{ route($route) }}" class="sli-nav-link" aria-label="{{ $label }}" @if(request()->routeIs($route)) aria-current="page" @endif>
        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span><span class="sli-nav-label">{{ $label }}</span>
        @if(request()->routeIs($route))<span class="sli-nav-dot" aria-hidden="true"></span>@endif
    </a><span class="sli-nav-tooltip" aria-hidden="true">{{ $label }}</span></div>
@endforeach
