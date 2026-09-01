<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Videollamada — LexCita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:wght@400;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface": "#121412", "surface-container": "#1f201e",
                        "surface-container-high": "#292a29", "on-surface": "#e3e2e0",
                        "on-surface-variant": "#c4c7c7", "outline": "#8e9192",
                        "outline-variant": "#444748", "secondary": "#e9c349",
                        "on-secondary": "#3c2f00", "error": "#ffb4ab", "error-container": "#93000a",
                    },
                    fontFamily: { "caslon": ["Libre Caslon Text","serif"], "grotesk": ["Hanken Grotesk","sans-serif"] },
                    borderRadius: { DEFAULT: "0", lg: "0", xl: "0", full: "9999px" },
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; vertical-align: middle; }
        body { background-color: #121412; color: #e3e2e0; font-family: 'Hanken Grotesk', sans-serif; }
        video { background: #0a0b0a; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">

{{-- Encabezado de sala --}}
<header class="flex items-center justify-between px-6 py-3 border-b border-white/10 bg-black/40">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-on-secondary text-xs font-bold">GC</div>
        <div>
            <p class="font-grotesk text-sm text-on-surface leading-tight">Videollamada — Cita {{ $cita->codigo }}</p>
            <p class="text-[11px] text-outline font-grotesk uppercase tracking-widest">{{ $esAbogado ? 'Abogado' : 'Cliente' }} · con {{ $peer->nombre }} · Virtual</p>
        </div>
    </div>
    <div id="estado-conexion" class="flex items-center gap-2 text-[11px] font-grotesk uppercase tracking-widest text-outline">
        <span id="estado-dot" class="w-2 h-2 rounded-full bg-outline"></span>
        <span id="estado-txt">Conectando…</span>
    </div>
</header>

{{-- Grid de video --}}
<main class="flex-1 flex flex-col md:flex-row gap-4 p-4 overflow-auto">
    <div class="flex-1 flex items-center justify-center relative rounded overflow-hidden bg-black/50 min-h-[220px]">
        <video id="video-remoto" autoplay playsinline class="w-full h-full object-cover"></video>
        <div id="placeholder-remoto" class="absolute inset-0 hidden flex-col items-center justify-center gap-2 text-outline">
            <span class="material-symbols-outlined text-[40px]">videocam_off</span>
            <p class="text-sm font-grotesk">Esperando a {{ $peer->nombre }}…</p>
        </div>
        <span class="absolute bottom-2 left-2 text-[11px] font-grotesk uppercase tracking-widest text-on-surface bg-black/70 px-2 py-1 rounded">{{ $peer->nombre }}</span>
    </div>
    <div class="w-full md:w-80 flex flex-col items-center justify-center relative rounded overflow-hidden bg-black/50 min-h-[180px]">
        <video id="video-local" autoplay playsinline muted class="w-full h-full object-cover"></video>
        <span class="absolute bottom-2 left-2 text-[11px] font-grotesk uppercase tracking-widest text-on-surface bg-black/70 px-2 py-1 rounded">Tú ({{ $user->nombre }})</span>
    </div>
</main>

{{-- Barra de controles --}}
<footer class="flex items-center justify-center gap-3 px-6 py-4 border-t border-white/10 bg-black/40">
    <button id="btn-mic" onclick="tc.toggleMic()"
        class="flex flex-col items-center gap-1 text-[11px] font-grotesk uppercase tracking-widest text-on-surface px-4 py-2 rounded bg-surface-container-high hover:brightness-125 transition">
        <span class="material-symbols-outlined text-[22px]">mic</span> Micrófono
    </button>
    <button id="btn-cam" onclick="tc.toggleCam()"
        class="flex flex-col items-center gap-1 text-[11px] font-grotesk uppercase tracking-widest text-on-surface px-4 py-2 rounded bg-surface-container-high hover:brightness-125 transition">
        <span class="material-symbols-outlined text-[22px]">videocam</span> Cámara
    </button>
    <button id="btn-cuelga" onclick="tc.cuelga()"
        class="flex flex-col items-center gap-1 text-[11px] font-grotesk uppercase tracking-widest text-on-error px-5 py-2 rounded bg-error-container hover:brightness-125 transition">
        <span class="material-symbols-outlined text-[22px]">call_end</span> Colgar
    </button>
</footer>

{{-- Variables inyectadas por Blade (server-side) --}}
<script id="datos-sala" type="application/json">
{
    "roomToken":  {{ Js::from($room->room_token) }},
    "channel":    {{ Js::from('video-room.' . $room->room_token) }},
    "myUserId":   {{ Js::from((string) $user->id) }},
    "peerUserId": {{ Js::from((string) $peer->id) }},
    "esAbogado":  {{ Js::from($esAbogado) }},
    "esPrimero":  {{ Js::from($esPrimero) }},
    "userNombre": {{ Js::from($user->nombre) }},
    "peerNombre": {{ Js::from($peer->nombre) }},
    "csrf":       {{ Js::from(csrf_token()) }},
    "stun":       {{ Js::from($stun['iceServers'] ?? []) }},
    "urlOffer":   {{ Js::from(route('video.offer', $room->room_token)) }},
    "urlAnswer":  {{ Js::from(route('video.answer', $room->room_token)) }},
    "urlIce":     {{ Js::from(route('video.ice', $room->room_token)) }},
    "urlLeave":   {{ Js::from(route('video.leave', $room->room_token)) }},
    "urlVolver":  {{ Js::from($esAbogado ? route('abogado.dashboard') : route('cliente.dashboard')) }}
}
</script>

<script>
(function () {
    'use strict';

    var datos   = JSON.parse(document.getElementById('datos-sala').textContent);
    var csrf    = datos.csrf;
    var pc      = null;
    var local   = null;
    var pendientes = [];   // cola de ICE hasta tener remoteDescription
    var micActivo  = true;
    var camActiva  = true;
    var conectado  = false;
    var cuelgaEnviado = false;

    var elVideoLocal = document.getElementById('video-local');
    var elVideoRemoto = document.getElementById('video-remoto');
    var elPlaceholder = document.getElementById('placeholder-remoto');
    var estadoDot = document.getElementById('estado-dot');
    var estadoTxt = document.getElementById('estado-txt');
    var btnMic = document.getElementById('btn-mic');
    var btnCam = document.getElementById('btn-cam');

    function setEstado(texto, color) {
        estadoTxt.textContent = texto;
        estadoDot.style.background = color || '#8e9192';
    }

    function headers(fn) {
        var h = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
        if (fn) h['X-Socket-ID'] = fn;
        return h;
    }

    async function post(url, body) {
        var res = await fetch(url, { method: 'POST', headers: headers(), body: JSON.stringify(body || {}) });
        if (!res.ok) { throw new Error('POST ' + url + ' -> ' + res.status); }
        return res.json();
    }

    function crearPeer() {
        pc = new RTCPeerConnection({ iceServers: datos.stun });

        pc.addEventListener('icecandidate', function (e) {
            if (e.candidate) {
                post(datos.urlIce, {
                    candidate: { candidate: e.candidate.candidate, sdpMid: e.candidate.sdpMid, sdpMLineIndex: e.candidate.sdpMLineIndex },
                    target_user_id: datos.peerUserId,
                }).catch(function () { /* best-effort ICE */ });
            }
        });

        pc.addEventListener('track', function (e) {
            if (e.streams && e.streams[0]) {
                elVideoRemoto.srcObject = e.streams[0];
                elPlaceholder.classList.add('hidden');
            }
        });

        pc.addEventListener('connectionstatechange', function () {
            if (pc.connectionState === 'connected') { conectado = true; setEstado('En llamada', '#4caf82'); }
            else if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
                if (!cuelgaEnviado) setEstado('Reconectando…', '#e9c349');
            }
        });
        return pc;
    }

    async function empezarStreamLocal() {
        local = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        elVideoLocal.srcObject = local;
        local.getTracks().forEach(function (t) { pc.addTrack(t, local); });
    }

    async function despacharPendientes() {
        if (!pc.remoteDescription) return;
        while (pendientes.length) { await pc.addIceCandidate(pendientes.shift()); }
    }

    async function enviarOferta() {
        var offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        setEstado('Esperando a ' + datos.peerNombre + '…', '#e9c349');
        await post(datos.urlOffer, { sdp: pc.localDescription, target_user_id: datos.peerUserId });
    }

    async function alRecibirOferta(data) {
        if (String(data.target_user_id) !== datos.myUserId) return;
        setEstado('Conectando…', '#e9c349');
        await pc.setRemoteDescription({ type: data.sdp.type, sdp: data.sdp.sdp });
        await despacharPendientes();
        var answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        await post(datos.urlAnswer, { sdp: pc.localDescription, target_user_id: datos.peerUserId });
    }

    async function alRecibirAnswer(data) {
        if (String(data.target_user_id) !== datos.myUserId) return;
        await pc.setRemoteDescription({ type: data.sdp.type, sdp: data.sdp.sdp });
        await despacharPendientes();
    }

    async function alRecibirIce(data) {
        if (String(data.target_user_id) !== datos.myUserId) return;
        var cand = data.candidate || {};
        var ice = { candidate: cand.candidate, sdpMid: cand.sdpMid, sdpMLineIndex: cand.sdpMLineIndex };
        if (pc && pc.remoteDescription) { await pc.addIceCandidate(ice).catch(function () {}); }
        else { pendientes.push(ice); }
    }

    function alRecibirLeft(data) {
        if (String(data.user_id) === datos.myUserId) return;
        setEstado(datos.peerNombre + ' abandonó la llamada', '#ffb4ab');
        if (elVideoRemoto.srcObject) { elVideoRemoto.srcObject.getTracks().forEach(function (t) { t.stop(); }); }
        elVideoRemoto.srcObject = null;
        elPlaceholder.classList.remove('hidden');
    }

    // ── Negociación (FASE 13/14): ABOGADO inicia, CLIENTE responde. ──
    // El abogado SOLO crea la oferta cuando sabe que el cliente está presente,
    // para no emitir una oferta que se pierda si el otro aún no se ha suscrito.
    var ofertaEnviada = false;

    async function iniciarNegociacionSiAbogado() {
        if (!datos.esAbogado) return;
        if (ofertaEnviada) return;
        ofertaEnviada = true;
        await enviarOferta();
    }

    function alRecibirJoined(data) {
        // Solo nos interesa la llegada del PEER (el otro participante).
        if (String(data.user_id) !== datos.peerUserId) return;
        // Si soy el abogado y el cliente acaba de unirse → iniciar la oferta.
        iniciarNegociacionSiAbogado();
    }

    window.tc = {
        toggleMic: function () {
            micActivo = !micActivo;
            if (local) local.getAudioTracks().forEach(function (t) { t.enabled = micActivo; });
            btnMic.querySelector('.material-symbols-outlined').textContent = micActivo ? 'mic' : 'mic_off';
        },
        toggleCam: function () {
            camActiva = !camActiva;
            if (local) local.getVideoTracks().forEach(function (t) { t.enabled = camActiva; });
            btnCam.querySelector('.material-symbols-outlined').textContent = camActiva ? 'videocam' : 'videocam_off';
        },
        cuelga: function () {
            if (cuelgaEnviado) return;
            cuelgaEnviado = true;
            post(datos.urlLeave, {}).catch(function () {});
            if (local) local.getTracks().forEach(function (t) { t.stop(); });
            if (pc) pc.close();
            window.location.href = datos.urlVolver;
        },
    };

    async function iniciar() {
        if (!window.Echo) { setEstado('Servicio de tiempo real no disponible', '#ffb4ab'); return; }
        pc = crearPeer();

        window.Echo.private(datos.channel)
            .listen('.webrtc.offer',           alRecibirOferta)
            .listen('.webrtc.answer',          alRecibirAnswer)
            .listen('.webrtc.ice-candidate',   alRecibirIce)
            .listen('.participant.joined',     alRecibirJoined)
            .listen('.participant.left',       alRecibirLeft);

        try {
            await empezarStreamLocal();
            setEstado('Listo. Conectando…', '#e9c349');
            if (datos.esAbogado) {
                // ABOGADO = iniciador, pero solo ofrece cuando el cliente está presente.
                if (!datos.esPrimero) {
                    // Ya hay otro participante (el cliente) → ofrecer ahora.
                    await iniciarNegociacionSiAbogado();
                } else {
                    // Soy el primero en llegar: espero el participant.joined del cliente.
                    setEstado('Esperando a ' + datos.peerNombre + '…', '#e9c349');
                }
            } else {
                // CLIENTE = respondedor: espera la oferta del abogado.
                setEstado('Esperando a ' + datos.peerNombre + '…', '#e9c349');
            }
        } catch (e) {
            setEstado('No se pudo acceder a cámara/micrófono', '#ffb4ab');
            elPlaceholder.classList.remove('hidden');
        }
    }

    // FASE 18: al cerrar/abandonar la pestaña, avisar al peer incluso si el
    // usuario no pulsó "Colgar". sendBeacon no depende de un fetch normal.
    window.addEventListener('pagehide', function () {
        if (cuelgaEnviado) return; // ya se notificó con el botón Colgar
        if (navigator.sendBeacon) {
            var fd = new FormData();
            fd.append('_token', csrf);
            navigator.sendBeacon(datos.urlLeave, fd);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
</script>

</body>
</html>
