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
</head>
<body class="min-h-screen flex">

{{-- ── TOP NAVBAR ────────────────────────────────────────── --}}
<nav class="fixed w-full bg-[#131317] border-b border-neutral-800 px-6 py-3 z-50">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Left: Brand -->
        <div class="flex items-baseline gap-2">
            <p class="text-2xl font-caslon text-gradient-gold">GC</p>
            <p class="text-[10px] font-grotesk uppercase text-neutral-400">PORTAL DEL CLIENTE</p>
        </div>

        <!-- Middle: Navigation Links -->
        <div class="hidden md:flex items-center gap-6">
            @auth
                @if(auth()->user()->esCliente())
                    <a href="{{ route('cliente.dashboard') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Dashboard
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('cliente.nueva-cita') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Nueva Cita
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('cliente.mis-citas') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Mis Citas
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                @elseif(auth()->user()->esAbogado())
                    <a href="{{ route('abogado.dashboard') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Dashboard
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('abogado.agenda') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Mi Agenda
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                @elseif(auth()->user()->esAdmin())
                    <a href="{{ route('interno.dashboard') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Dashboard
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('interno.abogados') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Abogados
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('interno.clientes') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Clientes
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('interno.citas') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                        Todas las Citas
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                    <a href="{{ route('interno.estadisticas') }}"
                       class="relative text-on-surface hover:text-primary transition-colors group cursor-pointer">
                       Estadísticas
                        <span class="absolute bottom-0 left-0 hidden group-hover:block w-full border-b-2 border-primary/50 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    </a>
                @endif
            @endauth
        </div>

        <!-- Right: User Profile -->
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-on-secondary text-xs font-bold">
                G
            </div>
            <div class="min-w-0">
                <p class="font-grotesk text-on-surface truncate">{{ auth()->user()->nombre }}</p>
                <p class="text-[10px] uppercase text-neutral-500">{{ ucfirst(auth()->user()->rol) }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-2 text-on-surface-variant hover:text-secondary text-sm py-1">
                    <span class="material-symbols-outlined text-[16px]">logout</span>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ── MAIN CONTENT ────────────────────────────────────────── --}}
<main class="w-full container mx-auto py-8">

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