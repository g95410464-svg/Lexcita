<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laureti &amp; Associates — Crear Cuenta</title>
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
                        "surface-container-lowest": "#0d0f0d",
                        "surface-container-low":    "#1a1c1a",
                        "on-surface":               "#e3e2e0",
                        "on-surface-variant":       "#c4c7c7",
                        "outline":                  "#8e9192",
                        "outline-variant":          "#444748",
                        "secondary":                "#e9c349",
                        "on-secondary":             "#3c2f00",
                        "error":                    "#ffb4ab",
                        "error-container":          "#93000a",
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

<div class="w-full max-w-4xl border border-outline-variant flex" style="min-height:560px">

    {{-- ── PANEL IZQUIERDO ──────────────────────────────── --}}
    <div class="hidden md:flex w-5/12 bg-surface-container-lowest border-r border-outline-variant flex-col justify-between p-12">
        <div>
            <p class="font-caslon text-2xl font-bold text-secondary tracking-wide">LAURETI</p>
            <p class="text-xs font-grotesk font-semibold tracking-[.18em] uppercase text-outline mt-0.5">&amp; Associates</p>
        </div>

        <div>
            <h1 class="font-caslon text-3xl font-normal leading-snug text-on-surface">
                Su causa merece<br>
                representación <span class="text-secondary">de élite.</span>
            </h1>
            <div class="mt-8 flex flex-col gap-4">
                @foreach(['Consultas confidenciales', 'Abogados especializados', 'Pago seguro con Stripe', 'Notificaciones por WhatsApp'] as $item)
                <div class="flex items-center gap-3">
                    <span class="w-4 h-px bg-secondary block flex-shrink-0"></span>
                    <p class="text-sm text-on-surface-variant">{{ $item }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <p class="text-xs text-outline tracking-wider">
            © {{ date('Y') }} Laureti &amp; Associates.
        </p>
    </div>

    {{-- ── PANEL DERECHO ────────────────────────────────── --}}
    <div class="flex-1 bg-surface-container-low flex flex-col justify-center p-10 md:p-12">

        <p class="text-xs font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-5">
            Portal del Cliente
        </p>
        <h2 class="font-caslon text-3xl font-normal text-on-surface mb-2">Crear Cuenta</h2>
        <p class="text-sm text-outline mb-8">Complete el formulario para acceder al portal.</p>

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

        <form method="POST" action="{{ route('registro.post') }}" novalidate>
            @csrf

            {{-- Nombre --}}
            <div class="mb-6">
                <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">
                    Nombre Completo
                </label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Juan Pérez" required
                    class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200">
            </div>

            {{-- Email --}}
            <div class="mb-6">
                <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">
                    Correo Electrónico
                </label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required
                    class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200">
            </div>

            {{-- WhatsApp --}}
            <div class="mb-6">
                <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">
                    WhatsApp <span class="text-secondary normal-case tracking-normal font-normal">(con código de país)</span>
                </label>
                <input type="tel" name="telefono_whatsapp" value="{{ old('telefono_whatsapp') }}" placeholder="+50312345678" required
                    class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200">
            </div>

            {{-- Contraseñas --}}
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">
                        Contraseña
                    </label>
                    <input type="password" name="password" placeholder="Mín. 8 caracteres" required
                        class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200">
                </div>
                <div>
                    <label class="block text-[11px] font-grotesk font-semibold tracking-[.12em] uppercase text-on-surface-variant mb-2">
                        Confirmar
                    </label>
                    <input type="password" name="password_confirmation" placeholder="Repite la contraseña" required
                        class="w-full bg-transparent border-0 border-b border-outline-variant text-on-surface font-grotesk text-[15px] py-2.5 px-0 focus:outline-none focus:border-secondary placeholder-outline-variant transition-colors duration-200">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-secondary text-on-secondary font-grotesk text-[13px] font-bold tracking-[.12em] uppercase py-4 hover:opacity-90 transition-opacity duration-200">
                CREAR CUENTA
            </button>
        </form>

        <div class="flex items-center gap-3 my-5">
            <div class="flex-1 h-px bg-outline-variant"></div>
            <span class="text-[11px] text-outline tracking-widest uppercase">o</span>
            <div class="flex-1 h-px bg-outline-variant"></div>
        </div>

        <p class="text-center text-sm text-outline">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-secondary font-semibold hover:underline ml-1">Iniciar sesión</a>
        </p>
    </div>

</div>

</body>
</html>
