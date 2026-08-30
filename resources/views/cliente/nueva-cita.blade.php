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

    /* Tarjetas abogado - usando micro-interacciones CSS */
    .abogado-card { cursor:pointer; }
    .abogado-card.selected {
        border-color: #e9c349;
        background: rgba(233, 195, 73, 0.08);
        box-shadow: 0 0 0 2px rgba(233, 195, 73, 0.2);
    }
    .abogado-card.selected .av-circle { background:#e9c349; color:#3c2f00; }

    /* Días del calendario - usando micro-interacciones CSS */
    .cal-day { padding:7px 4px; text-align:center; font-size:.82rem; font-family:'Hanken Grotesk',sans-serif;
               border:1px solid transparent; cursor:pointer; }
    .cal-day:not(.disabled):hover {
        background: rgba(233, 195, 73, 0.12);
        border-color: #e9c349;
        color: #e9c349;
    }
    .cal-day.selected {
        background: #e9c349;
        border-color: #e9c349;
        color: #3c2f00;
        font-weight: 700;
    }
    .cal-day.disabled { color:#444748; cursor:default; }

    /* Slots - usando micro-interacciones CSS */
    .slot-btn { padding:8px 12px; border:1px solid #444748; font-size:.78rem; font-family:'Hanken Grotesk',sans-serif;
                font-weight:600; letter-spacing:.08em; text-transform:uppercase; cursor:pointer;
                background:transparent; color:#c4c7c7; }
    .slot-btn:hover { border-color: #e9c349; color: #e9c349; background: rgba(233, 195, 73, 0.08); }
    .slot-btn.selected {
        border-color: #e9c349;
        background: #e9c349;
        color: #3c2f00;
    }
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
<div id="paso1" x-show="$store.booking.step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 1 — Selecciona un abogado
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($abogados as $ab)
        <div class="abogado-card selectable-card bg-surface-container border border-outline-variant p-5 flex items-center gap-4 transition-all duration-300 hover:border-secondary hover:shadow-lg hover:shadow-secondary/10 hover:-translate-y-1"
             @click="$store.booking.setAbogado({{ $ab->id }}, '{{ $ab->nombre }}'); window.triggerSelectAnim($el)"
             :class="{ 'selected': $store.booking.abogadoId === {{ $ab->id }} }">
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
    <input type="hidden" name="abogado_id" id="abogado_id" x-effect="$el.value = $store.booking.abogadoId || ''">
</div>

{{-- ── PASO 2: Fecha y hora ───────────────────────────── --}}
<div id="paso2" x-show="$store.booking.step >= 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4" class="mt-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 2 — Selecciona fecha y horario
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Calendario --}}
        <div class="bg-surface-container border border-outline-variant p-5 transition-all duration-300">
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
        <div class="bg-surface-container border border-outline-variant p-5 transition-all duration-300" x-data="{ slotsLoaded: false }" x-show="slotsLoaded || $store.booking.fechaSeleccionada" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
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
<div id="paso3" x-show="$store.booking.step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" style="display:none" class="mt-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 3 — Detalles de la consulta
    </p>
    <div class="bg-surface-container border border-outline-variant p-6 transition-all duration-300">

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
        <div id="resumen" x-show="$store.booking.horaSeleccionada" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="bg-surface-container-high border border-outline-variant p-4 mb-5 hidden">
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
// Alpine.js store para manejo de estado global
// Initialize Alpine store - works whether Alpine is already initialized or not
if (window.Alpine) {
    Alpine.store('booking', {
        step: 1,
        abogadoId: null,
        abogadoNom: '',
        fechaSeleccionada: null,
        horaSeleccionada: null,
        anio: new Date().getFullYear(),
        mes: new Date().getMonth(),

        setAbogado(id, nombre) {
            this.abogadoId = id;
            this.abogadoNom = nombre;
            this.step = 2;
        },

        setFecha(fecha) {
            this.fechaSeleccionada = fecha;
            this.horaSeleccionada = null;
        },

        setHora(hora) {
            this.horaSeleccionada = hora;
            this.step = 3;
        },

        volverPaso2() {
            this.step = 2;
            this.horaSeleccionada = null;
        },

        cambiarMes(dir) {
            this.mes += dir;
            if (this.mes < 0) { this.mes = 11; this.anio--; }
            if (this.mes > 11) { this.mes = 0;  this.anio++; }
        }
    });
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.store('booking', {
            step: 1,
            abogadoId: null,
            abogadoNom: '',
            fechaSeleccionada: null,
            horaSeleccionada: null,
            anio: new Date().getFullYear(),
            mes: new Date().getMonth(),

            setAbogado(id, nombre) {
                this.abogadoId = id;
                this.abogadoNom = nombre;
                this.step = 2;
            },

            setFecha(fecha) {
                this.fechaSeleccionada = fecha;
                this.horaSeleccionada = null;
            },

            setHora(hora) {
                this.horaSeleccionada = hora;
                this.step = 3;
            },

            volverPaso2() {
                this.step = 2;
                this.horaSeleccionada = null;
            },

            cambiarMes(dir) {
                this.mes += dir;
                if (this.mes < 0) { this.mes = 11; this.anio--; }
                if (this.mes > 11) { this.mes = 0;  this.anio++; }
            }
        });
    });
}

window.addEventListener('load', function () {

    // Helper: disparar animación de selección en elemento
    window.triggerSelectAnim = function(el) {
        el.classList.remove('animate-select');
        void el.offsetWidth;
        el.classList.add('animate-select');
    };

    // Scroll suave al paso 2 cuando se selecciona abogado.
    // Se usa Alpine.effect() porque Alpine.store() no expone el método $watch.
    let abogadoSeleccionado = false;
    Alpine.effect(() => {
        const abogadoId = Alpine.store('booking').abogadoId;
        if (abogadoId && !abogadoSeleccionado) {
            abogadoSeleccionado = true;
            setTimeout(() => {
                const paso2 = document.getElementById('paso2');
                if (paso2) paso2.scrollIntoView({behavior:'smooth', block:'start'});
            }, 100);
        }
    });

    // ─── Calendario ────────────────────────────────────────
    window.renderCalendario = function() {
        const store = Alpine.store('booking');
        const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        document.getElementById('cal-titulo').textContent = meses[store.mes] + ' ' + store.anio;

        const celdas = document.getElementById('cal-celdas');
        celdas.innerHTML = '';

        const primerDia = new Date(store.anio, store.mes, 1).getDay();
        const ajuste    = (primerDia === 0) ? 6 : primerDia - 1;
        const diasMes   = new Date(store.anio, store.mes + 1, 0).getDate();
        const hoy       = new Date(); hoy.setHours(0,0,0,0);

        for (let i = 0; i < ajuste; i++) {
            celdas.appendChild(document.createElement('div'));
        }

        for (let d = 1; d <= diasMes; d++) {
            const fecha      = new Date(store.anio, store.mes, d);
            const diaSemana  = fecha.getDay();
            const esFinSem   = (diaSemana === 0 || diaSemana === 6);
            const esPasado   = fecha < hoy;
            const fechaStr   = store.anio + '-' + String(store.mes+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');

            const div = document.createElement('div');
            div.className = 'cal-day selectable-card selectable-day transition-all duration-200' +
                (esFinSem || esPasado ? ' disabled' : '') +
                (fechaStr === store.fechaSeleccionada ? ' selected' : '');
            div.textContent = d;

            if (!esFinSem && !esPasado) {
                div.dataset.fecha = fechaStr;
                div.addEventListener('click', function() {
                    window.seleccionarFecha(this.dataset.fecha, this);
                });
            }
            celdas.appendChild(div);
        }
    };

    window.cambiarMes = function(dir) {
        Alpine.store('booking').cambiarMes(dir);
        window.renderCalendario();
    };

    window.seleccionarFecha = function(fecha, el) {
        Alpine.store('booking').setFecha(fecha);
        document.getElementById('fecha_input').value = fecha;
        document.getElementById('hora_inicio').value = '';

        // Disparar animación de selección en el elemento clickeado
        if (el) {
            el.classList.remove('animate-select');
            void el.offsetWidth;
            el.classList.add('animate-select');
        }

        window.renderCalendario();
        cargarSlots(fecha);
    };

    // ─── Slots ─────────────────────────────────────────────
    function cargarSlots(fecha) {
        const store = Alpine.store('booking');
        const cont = document.getElementById('slots-container');
        cont.innerHTML = '<div class="flex items-center gap-2 py-6 justify-center">' +
            '<span class="material-symbols-outlined text-outline animate-spin" style="font-size:22px;">refresh</span>' +
            '<p class="text-outline text-xs">Cargando horarios...</p></div>';

        fetch('/api/slots?abogado_id=' + store.abogadoId + '&fecha=' + fecha, {
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
            const grid = document.createElement('div');
            grid.className = 'flex flex-wrap gap-2';
            slots.forEach(function(slot) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'slot-btn selectable-card selectable-slot transition-all duration-200';
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
        Alpine.store('booking').setHora(hora);
        document.getElementById('hora_inicio').value = hora;
        document.querySelectorAll('.slot-btn').forEach(function(b) { b.classList.remove('selected'); });
        btn.classList.add('selected');

        // Disparar animación de selección
        btn.classList.remove('animate-select');
        void btn.offsetWidth;
        btn.classList.add('animate-select');

        const store = Alpine.store('booking');
        const fechaObj = new Date(store.fechaSeleccionada + 'T00:00:00');
        const opts = {weekday:'long', year:'numeric', month:'long', day:'numeric'};
        const resumen = document.getElementById('resumen');
        resumen.innerHTML =
            '<p class="text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-3">Resumen de tu cita</p>' +
            '<div class="flex flex-col gap-1.5">' +
            row('person', 'Abogado', store.abogadoNom) +
            row('calendar_today', 'Fecha', fechaObj.toLocaleDateString('es-SV', opts)) +
            row('schedule', 'Hora', horaLabel) +
            row('payments', 'Costo', '$35.00') +
            '</div>';

        activarStep('step-ind-3');
        setTimeout(() => {
            const paso3 = document.getElementById('paso3');
            if (paso3) paso3.scrollIntoView({behavior:'smooth', block:'start'});
        }, 100);
    };

    function row(icon, label, value) {
        return '<div class="flex items-center gap-2">' +
            '<span class="material-symbols-outlined text-outline" style="font-size:15px;">' + icon + '</span>' +
            '<span class="text-outline text-xs w-16">' + label + '</span>' +
            '<span class="text-on-surface text-sm font-grotesk font-semibold">' + value + '</span>' +
            '</div>';
    }

    window.volverPaso2 = function() {
        Alpine.store('booking').volverPaso2();
        document.getElementById('step-ind-3').classList.remove('step-active');
        document.getElementById('step-ind-3').classList.add('step-inactive');
    };

    function activarStep(id) {
        const el = document.getElementById(id);
        el.classList.remove('step-inactive');
        el.classList.add('step-active');
    }

    // Inicializar calendario
    window.renderCalendario();
});
</script>
@endpush