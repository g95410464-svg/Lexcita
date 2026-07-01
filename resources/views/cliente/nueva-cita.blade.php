@extends('layouts.app')
@section('title', 'Nueva Cita')

@section('content')

{{-- Encabezado --}}
<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-2">Portal del Cliente</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Agendar nueva cita</h1>
    <p class="text-outline mt-1 text-sm">Selecciona un abogado, fecha y horario disponible.</p>
</div>

{{-- Indicador de pasos --}}
<div class="flex items-center gap-0 mb-10">
    @foreach([['1','Abogado'],['2','Fecha y hora'],['3','Detalles']] as $i => $paso)
    <div id="step-ind-{{ $paso[0] }}" class="flex items-center gap-2 step-indicator {{ $i === 0 ? 'step-active' : 'step-inactive' }}">
        <div class="step-circle w-7 h-7 flex items-center justify-center text-[11px] font-grotesk font-bold border transition-colors duration-200">
            {{ $paso[0] }}
        </div>
        <span class="step-label text-[11px] font-grotesk font-semibold uppercase tracking-widest hidden sm:block">{{ $paso[1] }}</span>
    </div>
    @if($i < 2)
    <div class="flex-1 h-px bg-outline-variant mx-3 max-w-[60px]"></div>
    @endif
    @endforeach
</div>

@push('styles')
<style>
    .step-active .step-circle  { background:#e9c349; border-color:#e9c349; color:#3c2f00; }
    .step-active .step-label   { color:#e9c349; }
    .step-inactive .step-circle{ background:transparent; border-color:#444748; color:#8e9192; }
    .step-inactive .step-label { color:#8e9192; }
    /* Tarjetas abogado */
    .abogado-card { cursor:pointer; transition:border-color .15s, background .15s; }
    .abogado-card:hover { border-color:#8e9192; }
    .abogado-card.selected { border-color:#e9c349 !important; background:#1a1400 !important; }
    .abogado-card.selected .av-circle { background:#e9c349; color:#3c2f00; }
    /* Días del calendario */
    .cal-day { padding:7px 4px; text-align:center; font-size:.82rem; font-family:'Hanken Grotesk',sans-serif;
               border:1px solid transparent; cursor:pointer; transition:.12s; }
    .cal-day:not(.disabled):hover { border-color:#e9c349; color:#e9c349; }
    .cal-day.selected { background:#e9c349 !important; color:#3c2f00 !important; font-weight:700; border-color:#e9c349 !important; }
    .cal-day.disabled { color:#444748; cursor:default; }
    /* Slots */
    .slot-btn { padding:8px 12px; border:1px solid #444748; font-size:.78rem; font-family:'Hanken Grotesk',sans-serif;
                font-weight:600; letter-spacing:.08em; text-transform:uppercase; cursor:pointer;
                background:transparent; color:#c4c7c7; transition:.12s; }
    .slot-btn:hover   { border-color:#e9c349; color:#e9c349; }
    .slot-btn.selected{ background:#e9c349; color:#3c2f00; border-color:#e9c349; }
</style>
@endpush

@if($errors->any())
    <div class="flex items-start gap-3 bg-[#1a0a0a] border border-error-container px-4 py-3 mb-6">
        <span class="material-symbols-outlined text-error text-[18px] mt-0.5">error</span>
        <div>@foreach($errors->all() as $e)<p class="text-sm text-error">{{ $e }}</p>@endforeach</div>
    </div>
@endif

<form method="POST" action="{{ route('cliente.nueva-cita.post') }}" id="formCita">
@csrf

{{-- ── PASO 1: Abogado ────────────────────────────────── --}}
<div id="paso1">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 1 — Selecciona un abogado
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($abogados as $ab)
        <div class="abogado-card bg-surface-container border border-outline-variant p-5 flex items-center gap-4"
             data-id="{{ $ab->id }}"
             onclick="window.seleccionarAbogado({{ $ab->id }}, this)">
            <div class="av-circle w-11 h-11 rounded-full bg-surface-container-highest flex items-center justify-center
                        text-sm font-grotesk font-bold text-on-surface flex-shrink-0 transition-colors duration-150">
                {{ strtoupper(substr($ab->nombre, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-on-surface font-grotesk font-semibold text-sm truncate">{{ $ab->nombre }}</p>
                <p class="text-outline text-xs mt-0.5">{{ $ab->especialidad ?? 'Derecho General' }}</p>
            </div>
        </div>
        @endforeach
    </div>
    <input type="hidden" name="abogado_id" id="abogado_id">
</div>

{{-- ── PASO 2: Fecha y hora ───────────────────────────── --}}
<div id="paso2" style="display:none" class="mt-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 2 — Selecciona fecha y horario
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Calendario --}}
        <div class="bg-surface-container border border-outline-variant p-5">
            <div class="flex items-center justify-between mb-4">
                <button type="button"
                    onclick="window.cambiarMes(-1)"
                    class="w-8 h-8 flex items-center justify-center border border-outline-variant text-on-surface-variant hover:border-outline hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <span id="cal-titulo" class="font-grotesk font-semibold text-sm text-on-surface tracking-wide"></span>
                <button type="button"
                    onclick="window.cambiarMes(1)"
                    class="w-8 h-8 flex items-center justify-center border border-outline-variant text-on-surface-variant hover:border-outline hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
            {{-- Días de la semana --}}
            <div class="grid grid-cols-7 text-center mb-1">
                @foreach(['Lu','Ma','Mi','Ju','Vi','Sa','Do'] as $d)
                <div class="text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline py-1">{{ $d }}</div>
                @endforeach
            </div>
            <div id="cal-celdas" class="grid grid-cols-7 gap-px"></div>
        </div>

        {{-- Slots horarios --}}
        <div class="bg-surface-container border border-outline-variant p-5">
            <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
                Horarios disponibles
            </p>
            <div id="slots-container">
                <div class="flex flex-col items-center justify-center py-10 gap-2">
                    <span class="material-symbols-outlined text-outline" style="font-size:32px;">schedule</span>
                    <p class="text-outline text-xs">Selecciona una fecha primero.</p>
                </div>
            </div>
            <input type="hidden" name="hora_inicio" id="hora_inicio">
            <input type="hidden" name="fecha" id="fecha_input">
        </div>
    </div>
</div>

{{-- ── PASO 3: Detalles ───────────────────────────────── --}}
<div id="paso3" style="display:none" class="mt-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 3 — Detalles de la consulta
    </p>
    <div class="bg-surface-container border border-outline-variant p-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            {{-- Tipo --}}
            <div>
                <label class="block text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-2">
                    Tipo de consulta
                </label>
                <select name="tipo" required
                    class="w-full bg-surface-container-high border border-outline-variant text-on-surface
                           text-sm font-grotesk px-3 py-2.5 focus:outline-none focus:border-secondary transition-colors">
                    <option value="">Selecciona...</option>
                    <option value="consulta_general">Consulta general</option>
                    <option value="derecho_familiar">Derecho familiar</option>
                    <option value="derecho_penal">Derecho penal</option>
                    <option value="derecho_laboral">Derecho laboral</option>
                    <option value="derecho_civil">Derecho civil</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            {{-- Modalidad --}}
            <div>
                <label class="block text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-2">
                    Modalidad
                </label>
                <select name="modalidad" required
                    class="w-full bg-surface-container-high border border-outline-variant text-on-surface
                           text-sm font-grotesk px-3 py-2.5 focus:outline-none focus:border-secondary transition-colors">
                    <option value="presencial">Presencial</option>
                    <option value="virtual">Virtual</option>
                </select>
            </div>
        </div>

        {{-- Descripción --}}
        <div class="mb-5">
            <label class="block text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-2">
                Descripción breve <span class="normal-case tracking-normal font-normal">(opcional)</span>
            </label>
            <textarea name="descripcion" rows="3"
                placeholder="Describe brevemente el motivo de tu consulta..."
                class="w-full bg-surface-container-high border border-outline-variant text-on-surface text-sm
                       font-grotesk px-3 py-2.5 resize-none focus:outline-none focus:border-secondary transition-colors
                       placeholder:text-outline"></textarea>
        </div>

        {{-- Resumen --}}
        <div id="resumen" class="bg-surface-container-high border border-outline-variant p-4 mb-5 hidden">
        </div>

        {{-- Botones --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-secondary text-on-secondary text-[11px] font-grotesk
                       font-bold tracking-widest uppercase px-6 py-3 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[16px]">credit_card</span>
                Pagar y Confirmar — $35.00
            </button>
            <button type="button" onclick="window.volverPaso2()"
                class="inline-flex items-center gap-2 border border-outline-variant text-on-surface-variant
                       text-[11px] font-grotesk font-bold tracking-widest uppercase px-5 py-3
                       hover:border-outline hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Cambiar fecha
            </button>
        </div>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
window.addEventListener('load', function () {

    var abogadoId  = null;
    var abogadoNom = '';
    var fechaSel   = null;
    var horaSel    = null;
    var anio = new Date().getFullYear();
    var mes  = new Date().getMonth();

    // ─── Paso 1: Seleccionar abogado ───────────────────────
    window.seleccionarAbogado = function(id, el) {
        document.querySelectorAll('.abogado-card').forEach(function(c) { c.classList.remove('selected'); });
        el.classList.add('selected');
        abogadoId  = id;
        abogadoNom = el.querySelector('p').textContent;
        document.getElementById('abogado_id').value = id;

        var paso2 = document.getElementById('paso2');
        paso2.style.display = 'block';
        activarStep('step-ind-2');
        renderCalendario();
        paso2.scrollIntoView({behavior:'smooth', block:'start'});
    };

    // ─── Calendario ────────────────────────────────────────
    function renderCalendario() {
        var meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                     'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        document.getElementById('cal-titulo').textContent = meses[mes] + ' ' + anio;

        var celdas = document.getElementById('cal-celdas');
        celdas.innerHTML = '';

        var primerDia = new Date(anio, mes, 1).getDay();
        var ajuste    = (primerDia === 0) ? 6 : primerDia - 1;
        var diasMes   = new Date(anio, mes + 1, 0).getDate();
        var hoy       = new Date(); hoy.setHours(0,0,0,0);

        for (var i = 0; i < ajuste; i++) {
            celdas.appendChild(document.createElement('div'));
        }

        for (var d = 1; d <= diasMes; d++) {
            var fecha      = new Date(anio, mes, d);
            var diaSemana  = fecha.getDay();
            var esFinSem   = (diaSemana === 0 || diaSemana === 6);
            var esPasado   = fecha < hoy;
            var fechaStr   = anio + '-' + String(mes+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');

            var div = document.createElement('div');
            div.className = 'cal-day' + (esFinSem || esPasado ? ' disabled' : '') + (fechaStr === fechaSel ? ' selected' : '');
            div.textContent = d;

            if (!esFinSem && !esPasado) {
                div.dataset.fecha = fechaStr;
                div.addEventListener('click', function() {
                    window.seleccionarFecha(this.dataset.fecha, this);
                });
            }
            celdas.appendChild(div);
        }
    }

    window.cambiarMes = function(dir) {
        mes += dir;
        if (mes < 0)  { mes = 11; anio--; }
        if (mes > 11) { mes = 0;  anio++; }
        renderCalendario();
    };

    window.seleccionarFecha = function(fecha) {
        fechaSel = fecha;
        horaSel  = null;
        document.getElementById('fecha_input').value = fecha;
        document.getElementById('hora_inicio').value = '';
        renderCalendario();
        cargarSlots(fecha);
    };

    // ─── Slots ─────────────────────────────────────────────
    function cargarSlots(fecha) {
        var cont = document.getElementById('slots-container');
        cont.innerHTML = '<div class="flex items-center gap-2 py-6 justify-center">' +
            '<span class="material-symbols-outlined text-outline animate-spin" style="font-size:22px;">refresh</span>' +
            '<p class="text-outline text-xs">Cargando horarios...</p></div>';

        fetch('/api/slots?abogado_id=' + abogadoId + '&fecha=' + fecha, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(slots) {
            cont.innerHTML = '';
            if (!slots.length) {
                cont.innerHTML = '<div class="flex flex-col items-center py-8 gap-2">' +
                    '<span class="material-symbols-outlined text-outline" style="font-size:28px;">event_busy</span>' +
                    '<p class="text-outline text-xs">Sin horarios disponibles este día.</p></div>';
                return;
            }
            var grid = document.createElement('div');
            grid.className = 'flex flex-wrap gap-2';
            slots.forEach(function(slot) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'slot-btn';
                b.textContent = slot.hora_label;
                b.dataset.hora = slot.hora;
                b.addEventListener('click', function() {
                    window.seleccionarSlot(slot.hora, slot.hora_label, this);
                });
                grid.appendChild(b);
            });
            cont.appendChild(grid);
        })
        .catch(function() {
            cont.innerHTML = '<p class="text-error text-xs py-4">Error al cargar horarios. Intenta de nuevo.</p>';
        });
    }

    window.seleccionarSlot = function(hora, horaLabel, btn) {
        horaSel = hora;
        document.getElementById('hora_inicio').value = hora;
        document.querySelectorAll('.slot-btn').forEach(function(b) { b.classList.remove('selected'); });
        btn.classList.add('selected');

        var fechaObj = new Date(fechaSel + 'T00:00:00');
        var opts = {weekday:'long', year:'numeric', month:'long', day:'numeric'};
        var resumen = document.getElementById('resumen');
        resumen.classList.remove('hidden');
        resumen.innerHTML =
            '<p class="text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-3">Resumen de tu cita</p>' +
            '<div class="flex flex-col gap-1.5">' +
            row('person', 'Abogado', abogadoNom) +
            row('calendar_today', 'Fecha', fechaObj.toLocaleDateString('es-SV', opts)) +
            row('schedule', 'Hora', horaLabel) +
            row('payments', 'Costo', '$35.00') +
            '</div>';

        var paso3 = document.getElementById('paso3');
        paso3.style.display = 'block';
        activarStep('step-ind-3');
        paso3.scrollIntoView({behavior:'smooth', block:'start'});
    };

    function row(icon, label, value) {
        return '<div class="flex items-center gap-2">' +
            '<span class="material-symbols-outlined text-outline" style="font-size:15px;">' + icon + '</span>' +
            '<span class="text-outline text-xs w-16">' + label + '</span>' +
            '<span class="text-on-surface text-sm font-grotesk font-semibold">' + value + '</span>' +
            '</div>';
    }

    window.volverPaso2 = function() {
        document.getElementById('paso3').style.display = 'none';
        document.getElementById('step-ind-3').classList.remove('step-active');
        document.getElementById('step-ind-3').classList.add('step-inactive');
    };

    function activarStep(id) {
        var el = document.getElementById(id);
        el.classList.remove('step-inactive');
        el.classList.add('step-active');
    }
});
</script>
@endpush