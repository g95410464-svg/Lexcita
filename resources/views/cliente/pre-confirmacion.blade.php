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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(config('services.paypal.client_id'))
    <script src="https://www.paypal.com/sdk/js?client-id={{ urlencode(config('services.paypal.client_id')) }}&currency=USD&components=buttons"></script>
    @endif
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

    {{-- Sección de pago con PayPal inline (JS SDK + Orders API v2) --}}
    <div class="bg-surface-container border border-outline-variant p-6 mb-8 rounded-lg">
        <p class="text-[13px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-1">Paga tu cita</p>
        <p class="font-caslon text-3xl font-normal text-on-surface mb-4">${{ number_format($cita->monto, 2) }}</p>

        @if($errors->has('pago'))
            <div class="mb-4 p-3 bg-[#1a0a0a] border border-error-container text-error text-sm font-grotesk">
                {{ $errors->first('pago') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-[#1a0a0a] border border-error-container text-error text-sm font-grotesk">
                {{ session('error') }}
            </div>
        @endif

        <div id="paypal-mensaje" class="mb-4 text-sm font-grotesk text-on-surface-variant"></div>
        <div id="paypal-error" class="hidden mb-4 p-3 bg-[#1a0a0a] border border-error-container text-error text-sm font-grotesk"></div>

        @if(config('services.paypal.client_id'))
            <div id="paypal-button-container" class="min-h-[45px]"></div>
            <p class="mt-4 text-[11px] font-grotesk uppercase tracking-widest text-outline text-center">Pago procesado de forma segura por PayPal.</p>
        @else
            <p class="text-sm font-grotesk text-error mb-3">PayPal no está configurado en este entorno.</p>
        @endif

        <div class="mt-4 text-center">
            <a href="{{ route('cliente.paypal-pago', $cita->id) }}"
               class="text-[12px] font-grotesk font-semibold uppercase tracking-widest text-outline hover:text-secondary transition-colors">
                Pagar usando la página completa de PayPal
            </a>
        </div>
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

<script>
    (function () {
        var CITA_ID = {{ $cita->id }};
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var msj = document.getElementById('paypal-mensaje');
        var err = document.getElementById('paypal-error');

        function mostrarMensaje(texto) { if (msj) msj.textContent = texto; }
        function mostrarError(texto) {
            if (err) { err.textContent = texto; err.classList.remove('hidden'); }
        }
        function ocultarError() { if (err) err.classList.add('hidden'); }

        function headers() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            };
        }

        async function crearOrden() {
            ocultarError();
            mostrarMensaje('Procesando pago...');
            try {
                var res = await fetch('{{ route('cliente.paypal.create', $cita->id) }}', {
                    method: 'POST',
                    headers: headers(),
                });
                var data = await res.json();
                if (!res.ok || !data.orderID) {
                    mostrarError(data.message || 'No se pudo iniciar el pago con PayPal. Intenta nuevamente.');
                    throw new Error('createOrder falló');
                }
                return data.orderID;
            } catch (e) {
                mostrarError('No se pudo iniciar el pago con PayPal. Intenta nuevamente.');
                throw e;
            }
        }

        async function capturarOrden(orderID) {
            try {
                var res = await fetch('{{ route('cliente.paypal.capture', $cita->id) }}', {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify({ orderID: orderID }),
                });
                var data = await res.json();
                if (!res.ok || !data.success) {
                    mostrarError(data.message || 'No se pudo completar el pago con PayPal. Intenta nuevamente.');
                    return null;
                }
                return data;
            } catch (e) {
                mostrarError('No se pudo completar el pago con PayPal. Intenta nuevamente.');
                return null;
            }
        }

        if (typeof paypal !== 'undefined' && paypal.Buttons) {
            paypal.Buttons({
                style: { layout: 'vertical', shape: 'rect', color: 'gold', label: 'paypal' },
                createOrder: crearOrden,
                onApprove: async function (data) {
                    mostrarMensaje('Procesando pago...');
                    var result = await capturarOrden(data.orderID);
                    if (result && result.redirect) {
                        window.location.href = result.redirect;
                    }
                },
                onCancel: function () {
                    mostrarMensaje('El pago fue cancelado. Tu cita continúa pendiente de pago.');
                },
                onError: function () {
                    mostrarError('No se pudo completar el pago con PayPal. Intenta nuevamente.');
                },
            }).render('#paypal-button-container');
        } else {
            mostrarError('No se pudo cargar PayPal. Intenta nuevamente.');
        }
    })();
</script>

</body>
</html>