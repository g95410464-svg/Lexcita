<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sala de Videollamada — LexCita</title>

    {{-- FASE TEMPORAL: Jitsi IFrame API (incrustado, nunca redirección). --}}
    <script src="https://meet.jit.si/external_api.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:wght@400;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --surface: #121412;
            --surface-container: #1f201e;
            --surface-container-high: #292a29;
            --on-surface: #e3e2e0;
            --on-surface-variant: #c4c7c7;
            --outline: #8e9192;
            --secondary: #e9c349;
            --on-secondary: #3c2f00;
            --error: #ffb4ab;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            min-height: 100dvh;
            background-color: var(--surface);
            color: var(--on-surface);
            font-family: 'Hanken Grotesk', sans-serif;
            display: flex;
            flex-direction: column;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.10);
            background: rgba(0,0,0,0.40);
        }
        .brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .brand-logo {
            width: 34px; height: 34px; flex: none;
            border-radius: 50%;
            background: var(--secondary); color: var(--on-secondary);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
        }
        .brand-title { margin: 0; font-size: 13px; font-weight: 700; line-height: 1.1; color: var(--on-surface); }
        .brand-sub { margin: 0; font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--outline); }
        #estado-conexion {
            display: flex; align-items: center; gap: 6px;
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--outline);
            flex: none;
        }
        #estado-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--outline); }

        .meta-strip {
            display: flex; flex-wrap: wrap; gap: 6px 10px;
            padding: 8px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: var(--surface-container);
        }
        .meta-item { font-size: 11px; color: var(--on-surface-variant); }
        .meta-item strong { color: var(--on-surface); font-weight: 600; }

        main {
            flex: 1 1 auto;
            min-height: 0;
            padding: 12px;
            display: flex;
            flex-direction: column;
        }
        #jitsi-container {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            overflow: hidden;
            background: var(--surface-container);
        }
        #jitsi-container.hidden, .hidden { display: none; }
        .error-box {
            margin-top: 12px;
            padding: 12px 14px;
            border: 1px solid var(--error);
            background: rgba(255,180,171,0.10);
            color: var(--error);
            font-size: 13px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    {{-- Encabezado LexCita — la llamada permanece incrustada en la vista. --}}
    <header>
        <div class="brand">
            <div class="brand-logo">GC</div>
            <div>
                <p class="brand-title">TU CONEXIÓN LEGAL</p>
                <p class="brand-sub">Videollamada</p>
            </div>
        </div>
        <div id="estado-conexion">
            <span id="estado-dot"></span>
            <span id="estado-txt">Conectando…</span>
        </div>
    </header>

    {{-- Datos de la consulta. --}}
    <section class="meta-strip">
        <span class="meta-item">Cita <strong>{{ $cita->codigo }}</strong></span>
        <span class="meta-item">Cliente: <strong>{{ $cita->cliente->nombre }}</strong></span>
        <span class="meta-item">Abogado: <strong>{{ $cita->abogado->nombre }}</strong></span>
        <span class="meta-item">Fecha: <strong>{{ $cita->fecha->format('d/m/Y') }}</strong></span>
        <span class="meta-item">Hora: <strong>{{ \Illuminate\Support\Carbon::parse($cita->hora_inicio)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($cita->hora_fin)->format('H:i') }}</strong></span>
    </section>

    {{-- Jitsi ocupa la mayor parte de la pantalla (PC, tablet y móvil). --}}
    <main>
        <div id="jitsi-container"></div>
        <div id="jitsi-error" class="error-box hidden"></div>
    </main>

    {{-- Variables inyectadas por Blade (solo datos públicos necesarios). --}}
    <script id="datos-sala" type="application/json">
    {
        "roomName":     @json($room->jitsiRoomName()),
        "userNombre":   @json($user->nombre),
        "esAbogado":    @json($esAbogado),
        "urlVolver":    @json($esAbogado ? route('abogado.dashboard') : route('cliente.dashboard'))
    }
    </script>

    <script>
    (function () {
        'use strict';

        var datos;
        try {
            datos = JSON.parse(document.getElementById('datos-sala').textContent);
        } catch (e) {
            mostrarError('Datos de sala inválidos: ' + (e && e.message ? e.message : e));
            return;
        }

        var contenedor = document.getElementById('jitsi-container');
        var estadoTxt  = document.getElementById('estado-txt');
        var estadoDot  = document.getElementById('estado-dot');
        var volviendo  = false;

        function setEstado(texto, color) {
            if (estadoTxt) estadoTxt.textContent = texto;
            if (estadoDot) estadoDot.style.background = color || '#8e9192';
        }

        function mostrarError(msg) {
            var err = document.getElementById('jitsi-error');
            if (err) {
                err.classList.remove('hidden');
                err.textContent = msg;
            }
            if (contenedor) contenedor.classList.add('hidden');
        }

        // Al colgar: volver SOLO al dashboard según rol. No se cancela la cita,
        // no se toca payment_status y no se elimina la VideoRoom.
        function volver() {
            if (volviendo) return;
            volviendo = true;
            window.location.href = datos.urlVolver;
        }

        if (typeof JitsiMeetExternalAPI === 'undefined') {
            mostrarError('No se pudo cargar el módulo de videollamada (Jitsi). Comprueba la conexión y recarga la página.');
            return;
        }

        var api;
        try {
            api = new JitsiMeetExternalAPI('meet.jit.si', {
                roomName: datos.roomName,
                width: '100%',
                height: '100%',
                parentNode: contenedor,
                configOverwrite: {
                    prejoinPageEnabled: false,
                    MOBILE_APP_PROMO: false
                },
                interfaceConfigOverwrite: {
                    TOOLBAR_BUTTONS: ['microphone', 'camera', 'desktop', 'chat', 'tileview', 'fullscreen', 'hangup'],
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false
                },
                userInfo: {
                    displayName: datos.userNombre
                }
            });
        } catch (e) {
            mostrarError('Error al iniciar la videollamada: ' + (e && e.message ? e.message : e));
            return;
        }

        api.on('videoConferenceJoined', function () {
            setEstado('En llamada', '#4caf82');
        });

        api.on('participantLeft', function () {
            setEstado('Esperando al otro participante…', '#e9c349');
        });

        // Colgar / salir: el usuario ya colgó dentro de Jitsi.
        api.on('videoConferenceLeft', volver);
        api.on('readyToClose', volver);
    })();
    </script>

</body>
</html>