<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LexCita') — Laureti &amp; Associates</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:wght@400;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface":                  "#121412",
                        "surface-dim":              "#121412",
                        "surface-bright":           "#383938",
                        "surface-container-lowest": "#0d0f0d",
                        "surface-container-low":    "#1a1c1a",
                        "surface-container":        "#1f201e",
                        "surface-container-high":   "#292a29",
                        "surface-container-highest":"#343533",
                        "surface-variant":          "#343533",
                        "on-surface":               "#e3e2e0",
                        "on-surface-variant":       "#c4c7c7",
                        "outline":                  "#8e9192",
                        "outline-variant":          "#444748",
                        "secondary":                "#e9c349",
                        "secondary-container":      "#af8d11",
                        "on-secondary":             "#3c2f00",
                        "on-secondary-container":   "#342800",
                        "error":                    "#ffb4ab",
                        "error-container":          "#93000a",
                        "on-error-container":       "#ffdad6",
                        "primary":                  "#c9c6c5",
                        "on-primary":               "#313030",
                    },
                    fontFamily: {
                        "caslon":  ["Libre Caslon Text", "serif"],
                        "grotesk": ["Hanken Grotesk", "sans-serif"],
                    },
                    borderRadius: { DEFAULT: "0", lg: "0", xl: "0", full: "9999px" },
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; vertical-align: middle; }
        body { background-color: #121412; color: #e3e2e0; font-family: 'Hanken Grotesk', sans-serif; }
        /* Nav active bar */
        .nav-active { border-left: 2px solid #e9c349; background: rgba(233,195,73,.06); color: #e9c349; }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; } ::-webkit-scrollbar-track { background: #1a1c1a; } ::-webkit-scrollbar-thumb { background: #444748; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex">

{{-- ── SIDEBAR ──────────────────────────────────────────── --}}
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-surface-container border-r border-outline-variant flex flex-col py-8 z-50 transition-transform duration-200 -translate-x-full md:translate-x-0">

    {{-- Logo --}}
    <div class="px-6 mb-10">
        <p class="font-caslon text-xl font-bold text-secondary tracking-wide">LAURETI</p>
        <p class="text-[10px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mt-0.5">
            @auth
                @if(auth()->user()->esAdmin()) Admin Panel
                @elseif(auth()->user()->esAbogado()) Portal Abogado
                @else Portal del Cliente
                @endif
            @endauth
        </p>
    </div>

    {{-- Navegación --}}
    <nav class="flex flex-col flex-1 gap-0.5">
        @auth
            @if(auth()->user()->esCliente())
                <x-nav-link href="{{ route('cliente.dashboard') }}" :active="request()->routeIs('cliente.dashboard')" icon="dashboard">Dashboard</x-nav-link>
                <x-nav-link href="{{ route('cliente.nueva-cita') }}" :active="request()->routeIs('cliente.nueva-cita')" icon="add_circle">Nueva Cita</x-nav-link>
                <x-nav-link href="{{ route('cliente.mis-citas') }}" :active="request()->routeIs('cliente.mis-citas')" icon="calendar_month">Mis Citas</x-nav-link>

            @elseif(auth()->user()->esAbogado())
                <x-nav-link href="{{ route('abogado.dashboard') }}" :active="request()->routeIs('abogado.dashboard')" icon="dashboard">Dashboard</x-nav-link>
                <x-nav-link href="{{ route('abogado.agenda') }}" :active="request()->routeIs('abogado.agenda')" icon="calendar_month">Mi Agenda</x-nav-link>

            @elseif(auth()->user()->esAdmin())
                <x-nav-link href="{{ route('interno.dashboard') }}" :active="request()->routeIs('interno.dashboard')" icon="dashboard">Dashboard</x-nav-link>
                <x-nav-link href="{{ route('interno.abogados') }}" :active="request()->routeIs('interno.abogados')" icon="gavel">Abogados</x-nav-link>
                <x-nav-link href="{{ route('interno.clientes') }}" :active="request()->routeIs('interno.clientes')" icon="group">Clientes</x-nav-link>
                <x-nav-link href="{{ route('interno.citas') }}" :active="request()->routeIs('interno.citas')" icon="event_note">Todas las Citas</x-nav-link>
                <x-nav-link href="{{ route('interno.estadisticas') }}" :active="request()->routeIs('interno.estadisticas')" icon="bar_chart">Estadísticas</x-nav-link>
            @endif
        @endauth
    </nav>

    {{-- Footer sidebar --}}
    <div class="px-6 mt-auto">
        @auth
        {{-- Usuario --}}
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-on-secondary text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-on-surface truncate">{{ auth()->user()->nombre }}</p>
                <p class="text-[11px] text-outline uppercase tracking-wider">{{ ucfirst(auth()->user()->rol) }}</p>
            </div>
        </div>
        {{-- Cerrar sesión --}}
        <div class="border-t border-outline-variant pt-4 flex flex-col gap-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 text-on-surface-variant hover:text-secondary text-sm transition-colors duration-150 py-1">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span class="font-grotesk text-[13px]">Cerrar sesión</span>
                </button>
            </form>
        </div>
        @endauth
    </div>
</aside>

{{-- Burger mobile --}}
<button id="burger" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')"
    class="md:hidden fixed top-4 left-4 z-[60] w-10 h-10 bg-surface-container border border-outline-variant flex flex-col items-center justify-center gap-1.5">
    <span class="w-5 h-px bg-on-surface block"></span>
    <span class="w-5 h-px bg-on-surface block"></span>
    <span class="w-5 h-px bg-on-surface block"></span>
</button>

{{-- ── CONTENIDO ────────────────────────────────────────── --}}
<main class="flex-1 md:ml-64 min-h-screen">
    <div class="max-w-5xl mx-auto px-6 md:px-10 py-10">

        {{-- Alertas globales --}}
        @if(session('success'))
            <div class="flex items-center gap-3 bg-[#0a1a0f] border border-[#1a4d2a] px-4 py-3 mb-6">
                <span class="material-symbols-outlined text-[#4caf82] text-[18px]">check_circle</span>
                <p class="text-sm text-[#4caf82]">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-start gap-3 bg-[#1a0a0a] border border-error-container px-4 py-3 mb-6">
                <span class="material-symbols-outlined text-error text-[18px] mt-0.5">error</span>
                <div>
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-error">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>
