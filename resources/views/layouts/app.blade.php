<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LexCita') — GC Tu Conexión Legal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:wght@400;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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

    {{-- Estilos adicionales empujados por cada vista (@push('styles')) --}}
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    @stack('styles')
</head>
<body class="app-shell min-h-screen">

<a href="#contenido-principal" class="app-skip-link">Saltar al contenido</a>
<header class="app-header">
    <div class="app-header-inner">
        <div class="app-brand">
            <p class="text-2xl font-caslon text-secondary">GC</p>
            <p class="app-brand-label">{{ auth()->user()->esAdmin() ? 'Gestión interna' : (auth()->user()->esAbogado() ? 'Portal del abogado' : 'Portal del cliente') }}</p>
        </div>

        <nav class="app-desktop-nav" aria-label="Navegación principal">
            @include('layouts.navigation')
        </nav>

        <div class="app-profile">
            <div class="app-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</div>
            <div class="app-profile-info">
                <p class="truncate text-sm font-semibold" title="{{ auth()->user()->nombre }}">{{ auth()->user()->nombre }}</p>
                <p class="text-[10px] uppercase text-outline">{{ ucfirst(auth()->user()->rol) }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="app-logout" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                    <span class="app-logout-label">Salir</span>
                </button>
            </form>
        </div>
    </div>

    <details class="app-mobile-menu">
        <summary><span>Menú principal</span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span></summary>
        <nav aria-label="Navegación móvil">
            @include('layouts.navigation')
        </nav>
    </details>
</header>

<main id="contenido-principal" class="app-main" tabindex="-1">

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
</main>

@stack('scripts')
</body>
</html>