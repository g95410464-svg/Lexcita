<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GC Tu Conexión Legal — Ticket de Cita</title>
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
            <p class="text-xs font-grotesk font-semibold tracking-[.18em] uppercase text-outline mt-0.5">& Associates</p>
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

    {{-- ── PANEL DERECHO (ticket) ──────────────────────────────── --}}
    <div class="flex-1 bg-surface-container-low flex flex-col justify-center p-10 md:p-12">

        {{-- Etiqueta de sección --}}
        <p class="text-xs font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-5">
            Detalles de la Cita
        </p>

        <h2 class="font-caslon text-3xl font-normal text-on-surface mb-2">Ticket de Cita</h2>

        <div class="bg-surface-container border border-outline-variant p-6 mb-6">
            <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">Información de la Cita</p>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-[10px] text-outline uppercase tracking-wider">Código</p>
                    <p class="font-caslon text-2xl font-bold text-on-surface">{{ $cita->codigo }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-outline uppercase tracking-wider">Fecha</p>
                    <p class="font-caslon text-2xl font-bold text-on-surface">{{ $cita->fecha->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-[10px] text-outline uppercase tracking-wider">Hora</p>
                    <p class="font-caslon text-2xl font-bold text-on-surface">{{ $cita->hora_inicio }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-outline uppercase tracking-wider">Modalidad</p>
                    <p class="font-caslon text-2xl font-bold text-on-surface">{{ ucfirst($cita->modalidad) }}</p>
                </div>
            </div>
            <div>
                <p class="text-[10px] text-outline uppercase tracking-wider">Tipo</p>
                <p class="font-caslon text-2xl font-bold text-on-surface">{{ str_replace('_', ' ', $cita->tipo) }}</p>
            </div>
            <div class="mt-4">
                <p class="text-[10px] text-outline uppercase tracking-wider">Abogado</p>
                <p class="font-caslon text-xl font-medium text-on-surface">{{ $cita->abogado->nombre }}</p>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('cliente.mis-citas') }}" class="text-secondary hover:underline">Volver a mis citas</a>
        </div>

    </div>

</div>

</body>
</html>