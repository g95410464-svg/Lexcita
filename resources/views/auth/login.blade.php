<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GC Tu Conexión Legal — Iniciar Sesión</title>
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
                        "surface-container-lowest": "#0d0f0d",
                        "surface-container-low":    "#1a1c1a",
                        "surface-container":        "#1f201e",
                        "surface-container-high":   "#292a29",
                        "surface-variant":          "#343533",
                        "on-surface":               "#e3e2e0",
                        "on-surface-variant":       "#c4c7c7",
                        "outline":                  "#8e9192",
                        "outline-variant":          "#444748",
                        "secondary":                "#e9c349",
                        "on-secondary":             "#3c2f00",
                        "secondary-container":      "#af8d11",
                        "on-secondary-container":   "#342800",
                        "error":                    "#ffb4ab",
                        "error-container":          "#93000a",
                        "on-error-container":       "#ffdad6",
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
        input:-webkit-autofill { -webkit-box-shadow: 0 0 0 100px #1a1c1a inset !important; -webkit-text-fill-color: #e3e2e0 !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-4xl border border-outline-variant flex" style="min-height:520px">

    {{-- ── PANEL IZQUIERDO ──────────────────────────────── --}}
    <div class="hidden md:flex w-1/2 bg-surface-container-lowest border-r border-outline-variant flex-col justify-between p-12">
        {{-- Logo --}}
        <div>
            <p class="font-caslon text-2xl font-bold text-secondary tracking-wide">GC</p>
            <p class="text-xs font-grotesk font-semibold tracking-[.18em] uppercase text-outline mt-0.5">&amp; Associates</p>
        </div>

        {{-- Headline --}}
        <div>
            <h1 class="font-caslon text-3xl font-normal leading-snug text-on-surface">
                Acceso discreto.<br>
                Asesoría <span class="text-secondary">de élite.</span>
            </h1>
            <p class="text-sm text-outline mt-4 leading-relaxed">
                Su información está protegida bajo cifrado de grado militar y secreto profesional.
            </p>
        </div>

        {{-- Footer --}}
        <p class="text-xs text-outline tracking-wider">
            © {{ date('Y') }} GC Tu Conexión Legal. Asesoría Legal de Élite.
        </p>
    </div>

    {{-- ── PANEL DERECHO (formulario) ──────────────────── --}}
    <div class="flex-1 bg-surface-container-low flex flex-col justify-center p-10 md:p-12">

        {{-- Etiqueta de sección --}}
        <p class="text-xs font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-5">
            Portal del Cliente
        </p>

        <h2 class="font-caslon text-3xl font-normal text-on-surface mb-2">Iniciar Sesión</h2>
        <p class="text-sm text-outline mb-9">Accede a tu cuenta para gestionar tus citas.</p>

        {{-- Errores --}}
        @if ($errors->any())
            <div class="bg-[#1a0a0a] border border-error-container flex items-start gap-3 px-4 py-3 mb-6">
                <span class="material-symbols-outlined text-error text-lg mt-0.5">error</span>
                <div>
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-error">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Formulario --}}
        <form method="POST" action="{{ route('login.post') }}" novalidate>
            @csrf

            {{-- Email --}}
            <div class="mb-7">
                <label for="email"
                       class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">
                    Correo Electrónico
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="tu@correo.com"
                    required
                    autofocus
                    class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200"
                >
            </div>

            {{-- Contraseña --}}
            <div class="mb-8">
                <div class="flex justify-between items-center mb-2">
                    <label for="password"
                           class="text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant">
                        Contraseña
                    </label>
                    {{-- Puedes agregar recuperar contraseña aquí si lo implementas --}}
                    {{-- <a href="#" class="text-[11px] text-secondary hover:underline">¿Olvidaste tu contraseña?</a> --}}
                </div>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200"
                >
            </div>

            {{-- Botón principal --}}
            <button
                type="submit"
                class="w-full bg-secondary text-on-secondary font-grotesk text-[13px] font-bold tracking-[.12em] uppercase py-4 hover:opacity-90 transition-opacity duration-200">
                INGRESAR
            </button>
        </form>

        {{-- Separador --}}
        <div class="flex items-center gap-3 my-6">
            <div class="flex-1 h-px bg-outline-variant"></div>
            <span class="text-[11px] text-outline tracking-widest uppercase">o</span>
            <div class="flex-1 h-px bg-outline-variant"></div>
        </div>

        {{-- Registro --}}
        <p class="text-center text-sm text-outline">
            ¿Sin cuenta?
            <a href="{{ route('registro') }}" class="text-secondary font-semibold hover:underline ml-1">
                Crear cuenta gratuita
            </a>
        </p>
    </div>

</div>

</body>
</html>