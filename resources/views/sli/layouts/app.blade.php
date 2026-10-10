<!DOCTYPE html>
<html lang="es" data-portal="sli">
<head>@include('partials.head')</head>
<body class="sli-app">
@php
    $portalRole = auth()->user()->esAdmin() ? 'Administración' : (auth()->user()->esAbogado() ? 'Portal del abogado' : 'Portal del cliente');
    $homeRoute = auth()->user()->esAdmin() ? 'interno.dashboard' : (auth()->user()->esAbogado() ? 'abogado.dashboard' : 'cliente.dashboard');
@endphp
<a class="sli-skip" href="#contenido-principal">Saltar al contenido</a>
<aside class="sli-sidebar">
    <a class="sli-brand" href="{{ route($homeRoute) }}" aria-label="Servicios Legales Integrados, inicio">@include('partials.brand')</a>
    <p class="sli-nav-heading">TU ESPACIO DE GESTIÓN</p>
    <nav aria-label="Navegación principal">@include('layouts.navigation')</nav>
    <div class="sli-sidebar-bottom">
        <div class="sli-sidebar-note"><span class="material-symbols-outlined" aria-hidden="true">balance</span><p>Cerca de ti.<br><strong>En cada paso legal.</strong></p></div>
        <p>Servicios Legales Integrados<br><span>El Salvador</span></p>
    </div>
</aside>
<div class="sli-workspace">
    <header class="sli-topbar">
        <div class="sli-context"><span class="sli-context-kicker">SERVICIOS LEGALES INTEGRADOS</span><span>{{ $portalRole }}</span></div>
        <div class="sli-topbar-right"><time class="sli-today" datetime="{{ now()->toDateString() }}">{{ now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}</time>
            <details class="sli-user-menu"><summary aria-label="Menú de {{ auth()->user()->nombre }}"><span class="sli-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</span><span class="sli-user-name">{{ auth()->user()->nombre }}<small>{{ $portalRole }}</small></span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary><div><p>{{ auth()->user()->email }}</p><form action="{{ route('logout') }}" method="POST">@csrf<button type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>Cerrar sesión</button></form></div></details>
        </div>
    </header>
    <details class="sli-mobile-nav"><summary><span><strong>SLI</strong> Menú del portal</span><span class="material-symbols-outlined" aria-hidden="true">menu</span></summary><nav aria-label="Navegación móvil">@include('layouts.navigation')</nav></details>
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
