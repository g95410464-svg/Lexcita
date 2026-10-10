<!DOCTYPE html><html lang="es" data-portal="sli"><head>@include('partials.head')<script src="{{ $jitsi['scriptUrl'] }}" referrerpolicy="no-referrer"></script></head>
<body class="sli-video"><header class="sli-video-bar"><a class="sli-brand" href="{{ $esAbogado ? route('abogado.dashboard') : route('cliente.dashboard') }}">@include('partials.brand')</a><div id="estado-conexion" role="status"><span id="estado-dot"></span><span id="estado-txt">Conectando…</span></div><a class="sli-button sli-button-outline" href="{{ $esAbogado ? route('abogado.dashboard') : route('cliente.dashboard') }}">Volver a mi portal</a></header>
<section class="meta-strip" aria-label="Datos de la consulta"><span>Cita <strong>{{ $cita->codigo }}</strong></span><span>Cliente: <strong>{{ $cita->cliente->nombre }}</strong></span><span>Abogado: <strong>{{ $cita->abogado->nombre }}</strong></span><span>{{ $cita->fecha->format('d/m/Y') }} · {{ substr($cita->hora_inicio, 0, 5) }} – {{ substr($cita->hora_fin, 0, 5) }}</span></section>
@if($jitsi['provider'] === 'public')<aside class="meta-strip" role="note">@if($esAbogado)Para iniciar la consulta, autentícate cuando Jitsi lo solicite. Activa la sala de espera y admite únicamente a tu cliente.@else Si Jitsi indica que espera al moderador, espera a que tu abogado inicie la consulta.@endif</aside>@endif
<main aria-label="Videollamada"><div id="jitsi-container"></div><div id="jitsi-error" role="alert" class="error-box hidden"></div></main>
<script id="datos-sala" type="application/json">
    {
        "roomName":     @json($jitsi['roomName']),
        "domain":       @json($jitsi['domain']),
        "jwt":          @json($jitsi['jwt']),
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
            api = new JitsiMeetExternalAPI(datos.domain, {
                roomName: datos.roomName,
                jwt: datos.jwt || undefined,
                lang: 'es',
                width: '100%',
                height: '100%',
                parentNode: contenedor,
                configOverwrite: {
                    prejoinConfig: { enabled: true },
                    disableDeepLinking: true,
                    toolbarButtons: ['microphone', 'camera', 'desktop', 'chat', 'tileview', 'fullscreen', 'security', 'hangup']
                },
                interfaceConfigOverwrite: {
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


</body></html>