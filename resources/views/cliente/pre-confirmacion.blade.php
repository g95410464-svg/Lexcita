<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GC Tu Conexión Legal — Pre-Confirmación de Pago</title>
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

<div class="w-full max-w-lg border border-outline-variant rounded-lg p-8 bg-surface-container-lowest mx-auto">

    {{-- Mensaje destacado --}}
    <div class="bg-surface-container border border-outline-variant p-6 mb-8 rounded-lg">
        <p class="text-[13px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">Estado de tu cita</p>
        <h2 class="font-caslon text-2xl font-normal text-on-surface mb-2">Tu cita está en proceso de agendación</h2>
        <p class="text-lg text-on-surface leading-relaxed">
            Tu cita está en proceso de agendación. Por favor continua con el pago. Si el pago no se efectúa o no se realiza con éxito, la cita no será confirmada y quedará cancelada/sin reservar.
        </p>
    </div>

    {{-- Botón de acción principal --}}
    <div class="text-center">
        <a href="{{ route('cliente.paypal-pago', $cita->id) }}"
           class="inline-flex items-center gap-2 bg-secondary text-on-secondary text-[12px] font-grotesk font-bold tracking-widest uppercase py-4 hover:opacity-90 transition-opacity duration-200">
            <span class="material-symbols-outlined text-[18px]">credit_card</span>
            Pagar cita
        </a>
    </div>

    {{-- Información de la cita --}}
    <div class="mt-6 p-4 bg-surface-container border border-outline-variant rounded-lg">
        <p class="text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-3">Datos de la cita</p>
        <div class="grid grid-cols-2 gap-2 text-[11px] font-grotesk text-on-surface-variant">
            <div>
                <span class="text-outline uppercase tracking-widest">Código</span>
                <p class="font-grotesk font-medium text-on-surface">{{ $cita->codigo }}</p>
            </div>
            <div>
                <span class="text-outline uppercase tracking-widest">Abogado</span>
                <p class="font-grotesk font-medium text-on-surface">{{ $cita->abogado->nombre }}</p>
            </div>
            <div>
                <span class="text-outline uppercase tracking-widest">Fecha</span>
                <p class="font-grotesk font-medium text-on-surface">{{ $cita->fecha->format('d/m/Y') }}</p>
            </div>
            <div>
                <span class="text-outline uppercase tracking-widest">Hora</span>
                <p class="font-grotesk font-medium text-on-surface">{{ $cita->hora_inicio }}</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>