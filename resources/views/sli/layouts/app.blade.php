<!DOCTYPE html>
<html lang="es" data-portal="sli">
<head>@include('partials.head')
<script>
    try {
        if (localStorage.getItem('sli.sidebar.compact') === 'true') document.documentElement.dataset.sliSidebar = 'compact';
    } catch (_) { /* Navigation remains usable when browser storage is unavailable. */ }
</script>
</head>
<body class="sli-app">
@php
    $portalRole = auth()->user()->esAdmin() ? 'Administración' : (auth()->user()->esAbogado() ? 'Portal del abogado' : 'Portal del cliente');
    $homeRoute = auth()->user()->esAdmin() ? 'interno.dashboard' : (auth()->user()->esAbogado() ? 'abogado.dashboard' : 'cliente.dashboard');
@endphp
<a class="sli-skip" href="#contenido-principal">Saltar al contenido</a>
{{-- Sidebar structure adapted from the CodingNepal template supplied by the user. --}}
<aside class="sli-sidebar" id="sli-sidebar" aria-label="Menú del portal">
    <header class="sli-sidebar-header">
        <a class="sli-sidebar-logo" href="{{ route($homeRoute) }}" aria-label="Servicios Legales Integrados, inicio">
            <img class="sli-sidebar-full-logo" src="{{ asset('images/sli-firma.png') }}" alt="G.C. Tu Conexión Legal · Abogados y Notarios" width="1536" height="585">
            <span class="sli-sidebar-mini" aria-hidden="true">SLI</span>
        </a>
        <button class="sli-sidebar-toggler" type="button" data-sidebar-toggle aria-label="Contraer barra lateral" aria-controls="sli-sidebar-navigation" aria-expanded="true"><span class="material-symbols-outlined" aria-hidden="true">chevron_left</span></button>
        <button class="sli-menu-toggler" type="button" data-sidebar-menu-toggle aria-label="Mostrar menú" aria-controls="sli-sidebar-navigation" aria-expanded="false"><span class="material-symbols-outlined" aria-hidden="true">menu</span></button>
    </header>
    <div class="sli-sidebar-navigation" id="sli-sidebar-navigation">
        <p class="sli-sidebar-identity">Servicios Legales Integrados<span>S.A. de C.V. · El Salvador</span></p>
        <p class="sli-nav-heading">TU ESPACIO DE GESTIÓN</p>
        <nav aria-label="Navegación principal">@include('layouts.navigation')</nav>
        <div class="sli-sidebar-bottom">
            <div class="sli-sidebar-profile"><span class="sli-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</span><div><strong>{{ auth()->user()->nombre }}</strong><span>{{ $portalRole }}</span></div></div>
            <form action="{{ route('logout') }}" method="POST" class="sli-nav-item">@csrf<button class="sli-nav-link sli-sidebar-logout" type="submit" aria-label="Cerrar sesión"><span class="material-symbols-outlined" aria-hidden="true">logout</span><span class="sli-nav-label">Cerrar sesión</span></button><span class="sli-nav-tooltip" aria-hidden="true">Cerrar sesión</span></form>
        </div>
    </div>
</aside>
<button class="sli-sidebar-backdrop" type="button" data-sidebar-backdrop aria-label="Cerrar menú" tabindex="-1" hidden></button>
<div class="sli-workspace">
    <header class="sli-topbar">
        <div class="sli-context"><span class="sli-context-kicker">SERVICIOS LEGALES INTEGRADOS</span><span>{{ $portalRole }}</span></div>
        <div class="sli-topbar-right"><time class="sli-today" datetime="{{ now()->toDateString() }}">{{ now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}</time>
            <details class="sli-user-menu"><summary aria-label="Menú de {{ auth()->user()->nombre }}"><span class="sli-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</span><span class="sli-user-name">{{ auth()->user()->nombre }}<small>{{ $portalRole }}</small></span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary><div><p>{{ auth()->user()->email }}</p><form action="{{ route('logout') }}" method="POST">@csrf<button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Cerrar sesión</button></form></div></details>
        </div>
    </header>
    <main class="sli-main" id="contenido-principal" tabindex="-1">
        <div class="sli-breadcrumb"><a href="{{ route($homeRoute) }}">Mi portal</a><span aria-hidden="true">/</span><span>@yield('title', 'Resumen')</span></div>
        @if(session('success'))<div class="sli-alert sli-alert-success" role="status"><span class="material-symbols-outlined" aria-hidden="true">check_circle</span><p>{{ session('success') }}</p></div>@endif
        @if(session('error'))<div class="sli-alert sli-alert-error" role="alert">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="sli-alert sli-alert-error" role="alert"><span class="material-symbols-outlined" aria-hidden="true">error</span><div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div></div>@endif
        @yield('content')
    </main>
    <footer class="sli-footer"><span>© {{ now()->year }} Servicios Legales Integrados S.A. de C.V.</span><a href="https://servicioslegalesint.com/" target="_blank" rel="noopener noreferrer">Conoce nuestro despacho ↗</a></footer>
</div>
@stack('scripts')
</body></html>
