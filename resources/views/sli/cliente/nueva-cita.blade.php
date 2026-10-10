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
    <div id="step-ind-{{ $paso[0] }}" x-data :class="$store.booking.step >= {{ $paso[0] }} ? 'step-active' : 'step-inactive'" class="flex items-center gap-2 step-indicator">
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
    .step-active .step-circle  { background:#173b59; border-color:#173b59; color:#ffffff; }
    .step-active .step-label   { color:#173b59; }
    .step-inactive .step-circle{ background:transparent; border-color:#d7e0e8; color:#67788b; }
    .step-inactive .step-label { color:#67788b; }

    /* Tarjetas abogado - usando micro-interacciones CSS */
    .abogado-card { cursor:pointer; }
    .abogado-card.selected {
        border-color: #173b59;
        background: rgba(23,59,89, 0.08);
        box-shadow: 0 0 0 2px rgba(23,59,89, 0.2);
    }
    .abogado-card.selected .av-circle { background:#173b59; color:#ffffff; }

    /* Días del calendario - usando micro-interacciones CSS */
    .cal-day { padding:7px 4px; text-align:center; font-size:.82rem; font-family:'Hanken Grotesk',sans-serif;
               border:1px solid transparent; cursor:pointer; }
    .cal-day:not(.disabled):hover {
        background: rgba(23,59,89, 0.12);
        border-color: #173b59;
        color: #173b59;
    }
    .cal-day.selected {
        background: #173b59;
        border-color: #173b59;
        color: #ffffff;
        font-weight: 700;
    }
    .cal-day.disabled { color:#d7e0e8; cursor:default; }

    /* Calendario: cabecera y celdas comparten EXACTAMENTE 7 columnas iguales y el mismo gap */
    .cal-weekdays,
    #cal-celdas {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 1px;
    }

    /* Celdas vacías de desplazamiento (antes del día 1): misma geometría, sin interacción */
    .cal-empty {
        min-height: 42px;
        padding: 7px 4px;
    }

    /* Geometría consistente y centrado de los días */
    #cal-celdas .cal-day {
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Slots - usando micro-interacciones CSS */
    .slot-btn { padding:8px 12px; border:1px solid #d7e0e8; font-size:.78rem; font-family:'Hanken Grotesk',sans-serif;
                font-weight:600; letter-spacing:.08em; text-transform:uppercase; cursor:pointer;
                background:transparent; color:#536477; }
    .slot-btn:hover { border-color: #173b59; color: #173b59; background: rgba(23,59,89, 0.08); }
    .slot-btn.selected {
        border-color: #173b59;
        background: #173b59;
        color: #ffffff;
    }
</style>
@endpush

<form method="POST" action="{{ route('cliente.nueva-cita.post') }}" id="formCita" x-data>
@csrf

{{-- ── PASO 1: Abogado ────────────────────────────────── --}}
<div id="paso1" x-show="$store.booking.step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 1 — Selecciona un abogado
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @if($abogados->isEmpty())<div class="sli-empty col-span-full"><span class="material-symbols-outlined" aria-hidden="true">person_search</span><h3>Aún no hay abogados disponibles</h3><p>Contacta al despacho para recibir ayuda con tu consulta.</p><a class="sli-text-link" href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contactar al despacho</a></div>@endif
        @foreach($abogados as $ab)
        <button type="button" class="text-left w-full abogado-card selectable-card bg-surface-container border border-outline-variant p-5 flex items-center gap-4 transition-all duration-300 hover:border-secondary hover:shadow-lg hover:shadow-secondary/10 hover:-translate-y-1"
             @click="$store.booking.setAbogado({{ $ab->id }}, {{ json_encode($ab->nombre) }}); window.triggerSelectAnim($el)"
             :aria-pressed="$store.booking.abogadoId === {{ $ab->id }}" :class="{ 'selected': $store.booking.abogadoId === {{ $ab->id }} }">
            <div class="av-circle w-11 h-11 rounded-full bg-surface-container-highest flex items-center justify-center
                        text-sm font-grotesk font-bold text-on-surface flex-shrink-0 transition-colors duration-150">
                {{ strtoupper(substr($ab->nombre, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-on-surface font-grotesk font-semibold text-sm truncate">{{ $ab->nombre }}</p>
                <p class="text-outline text-xs mt-0.5">{{ $ab->especialidad ?? 'Derecho General' }}</p>
            </div>
        </button>
        @endforeach
    </div>
    <input type="hidden" name="abogado_id" id="abogado_id" x-effect="$el.value = $store.booking.abogadoId || ''">
</div>

{{-- ── PASO 2: Fecha y hora ───────────────────────────── --}}
<div id="paso2" x-cloak x-show="$store.booking.step >= 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4" class="mt-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5"><p class="text-sm">Tu abogado: <strong x-text="$store.booking.abogadoNom"></strong></p><button type="button" class="sli-button sli-button-outline" @click="$store.booking.step = 1">Cambiar abogado</button></div>
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 2 — Selecciona fecha y horario
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Calendario --}}
        <div class="bg-surface-container border border-outline-variant p-5 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <button type="button"
                    aria-label="Mes anterior" onclick="window.cambiarMes(-1)"
                    class="w-8 h-8 flex items-center justify-center border border-outline-variant text-on-surface-variant hover:border-outline hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <span id="cal-titulo" class="font-grotesk font-semibold text-sm text-on-surface tracking-wide"></span>
                <button type="button"
                    aria-label="Mes siguiente" onclick="window.cambiarMes(1)"
                    class="w-8 h-8 flex items-center justify-center border border-outline-variant text-on-surface-variant hover:border-outline hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
            {{-- Días de la semana --}}
            <div class="cal-weekdays grid grid-cols-7 text-center mb-1">
                @foreach(['Lu','Ma','Mi','Ju','Vi','Sa','Do'] as $d)
                <div class="text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline py-1">{{ $d }}</div>
                @endforeach
            </div>
            <div id="cal-celdas" class="grid grid-cols-7 gap-px"></div>
        </div>

        {{-- Slots horarios --}}
        <div class="bg-surface-container border border-outline-variant p-5 transition-all duration-300">
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
<div id="paso3" x-cloak x-show="$store.booking.step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" style="display:none" class="mt-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">
        Paso 3 — Detalles de la consulta
    </p>
    <div class="bg-surface-container border border-outline-variant p-6 transition-all duration-300">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            {{-- Tipo --}}
            <div>
                <label for="tipo" class="block text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-2">
                    Tipo de consulta
                </label>
                <select id="tipo" name="tipo" required
                    class="w-full min-w-0 bg-surface-container-high border border-outline-variant text-on-surface
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
                <label for="modalidad" class="block text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-2">
                    Modalidad
                </label>
                <select id="modalidad" name="modalidad" required
                    class="w-full min-w-0 bg-surface-container-high border border-outline-variant text-on-surface
                           text-sm font-grotesk px-3 py-2.5 focus:outline-none focus:border-secondary transition-colors">
                    <option value="presencial">Presencial</option>
                    <option value="virtual">Virtual</option>
                </select>
            </div>
        </div>

        {{-- Descripción --}}
        <div class="mb-5">
            <label for="descripcion" class="block text-[11px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-2">
                Descripción breve <span class="normal-case tracking-normal font-normal">(opcional)</span>
            </label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                placeholder="Describe brevemente el motivo de tu consulta..."
                class="w-full min-w-0 bg-surface-container-high border border-outline-variant text-on-surface text-sm
                       font-grotesk px-3 py-2.5 resize-none focus:outline-none focus:border-secondary transition-colors
                       placeholder:text-outline"></textarea>
        </div>

        {{-- Resumen --}}
        <div id="resumen" x-cloak x-show="$store.booking.horaSeleccionada" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="bg-surface-container-high border border-outline-variant p-4 mb-5">
        </div>

        {{-- Botones --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 bg-secondary text-on-secondary text-[11px] font-grotesk
                       font-bold tracking-widest uppercase px-6 py-3 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[16px]">credit_card</span>
                Continuar al pago — $35.00
            </button>
            <button type="button" onclick="window.volverPaso2()"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-2 border border-outline-variant text-on-surface-variant
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
<script src="{{ asset('js/booking-slots.js') }}"></script>
<script>
// Alpine.js store para manejo de estado global – inicializado vía alpine:init

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
            if (this.abogadoId !== id) {
                this.fechaSeleccionada = null;
                this.horaSeleccionada = null;
                window.limpiarSlots?.();
            }
            this.abogadoId = id;
            this.abogadoNom = nombre;
            this.step = 2;
        },

        setFecha(fecha) {
            this.fechaSeleccionada = fecha;
            this.horaSeleccionada = null;
            this.step = 2;
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
            if (this.mes > 11) { this.mes = 0; this.anio++; }
        }
    });

});

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
        const fechaFeria = @json(\App\Services\CitaService::FECHA_FERIA);

        for (let i = 0; i < ajuste; i++) {
            const vacio = document.createElement('div');
            vacio.className = 'cal-empty';
            celdas.appendChild(vacio);
        }

        for (let d = 1; d <= diasMes; d++) {
            const fecha      = new Date(store.anio, store.mes, d);
            const diaSemana  = fecha.getDay();
            const esFinSem   = (diaSemana === 0 || diaSemana === 6);
            const esPasado   = fecha < hoy;
            const fechaStr   = store.anio + '-' + String(store.mes+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            const bloqueado  = esPasado || (esFinSem && fechaStr !== fechaFeria);

            const div = document.createElement('button');
            div.type = 'button';
            div.disabled = bloqueado;
            div.setAttribute('aria-label', d + ' de ' + meses[store.mes] + ' de ' + store.anio);
            div.setAttribute('aria-pressed', String(fechaStr === store.fechaSeleccionada));
            div.className = 'cal-day selectable-card selectable-day transition-all duration-200' +
                (bloqueado ? ' disabled' : '') +
                (fechaStr === store.fechaSeleccionada ? ' selected' : '');
            div.textContent = d;

            if (!bloqueado) {
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
    const cont = document.getElementById('slots-container');
    const slotsLoader = window.createSlotLoader({
        url: @json(route('api.slots')),
        onLoading() {
            cont.innerHTML = '<div class="flex items-center gap-2 py-6 justify-center">' +
            '<span class="material-symbols-outlined text-outline animate-spin" style="font-size:22px;">refresh</span>' +
            '<p class="text-outline text-xs">Cargando horarios...</p></div>';
        },
        onSuccess(slots) {
            cont.innerHTML = '';
            if (!slots.length) {
                cont.innerHTML = '<div class="flex flex-col items-center py-8 gap-2">' +
                    '<span class="material-symbols-outlined text-outline" style="font-size:28px;">event_busy</span>' +
                    '<p class="text-outline text-xs">Sin horarios disponibles este día.</p></div>';
                return;
            }
            const grid = document.createElement('div');
            grid.className = 'flex flex-wrap gap-2';
            slots.forEach(slot => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'slot-btn selectable-card selectable-slot transition-all duration-200';
                b.textContent = slot.hora_label;
                b.dataset.hora = slot.hora;
                b.addEventListener('click', () => {
                    window.seleccionarSlot(slot.hora, slot.hora_label, b);
                });
                grid.appendChild(b);
            });
            cont.appendChild(grid);
        },
        onError(error) {
            const message = document.createElement('p');
            message.className = 'text-error text-xs py-4';
            message.textContent = error.status === 429
                ? error.message : 'Error al cargar horarios. Intenta de nuevo.';
            cont.replaceChildren(message);
        },
    });

    function cargarSlots(fecha) {
        slotsLoader.select(Alpine.store('booking').abogadoId, fecha);
    }

    window.limpiarSlots = function () {
        slotsLoader.reset();
        document.getElementById('fecha_input').value = '';
        document.getElementById('hora_inicio').value = '';
        cont.innerHTML = '<p class="text-outline text-xs py-4">Selecciona una fecha para ver los horarios.</p>';
        document.getElementById('resumen').replaceChildren();
        window.renderCalendario();
    };
    window.addEventListener('pagehide', () => slotsLoader.reset());

    window.seleccionarSlot = function(hora, horaLabel, btn) {
        Alpine.store('booking').setHora(hora);
        document.getElementById('hora_inicio').value = hora;
        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');

        // Disparar animación de selección
        btn.classList.remove('animate-select');
        void btn.offsetWidth;
        btn.classList.add('animate-select');

        const store = Alpine.store('booking');
        const fechaObj = new Date(store.fechaSeleccionada + 'T00:00:00');
        const opts = {weekday:'long', year:'numeric', month:'long', day:'numeric'};
        const resumen = document.getElementById('resumen');
        const heading = document.createElement('p');
        heading.className = 'text-[10px] font-grotesk font-semibold uppercase tracking-widest text-outline mb-3';
        heading.textContent = 'Resumen de tu cita';
        const details = document.createElement('div');
        details.className = 'flex flex-col gap-1.5';
        details.append(
            row('person', 'Abogado', store.abogadoNom),
            row('calendar_today', 'Fecha', fechaObj.toLocaleDateString('es-SV', opts)),
            row('schedule', 'Hora', horaLabel),
            row('payments', 'Costo', '$35.00'),
        );
        resumen.replaceChildren(heading, details);

        activarStep('step-ind-3');
        setTimeout(() => {
            const paso3 = document.getElementById('paso3');
            if (paso3) paso3.scrollIntoView({behavior:'smooth', block:'start'});
        }, 100);
    };

    function row(icon, label, value) {
        const line = document.createElement('div');
        line.className = 'flex items-start gap-2';
        const symbol = document.createElement('span');
        symbol.className = 'material-symbols-outlined text-outline';
        symbol.setAttribute('aria-hidden', 'true');
        symbol.style.fontSize = '15px';
        symbol.textContent = icon;
        const name = document.createElement('span');
        name.className = 'text-outline text-xs w-16 shrink-0';
        name.textContent = label;
        const content = document.createElement('span');
        content.className = 'text-on-surface text-sm font-grotesk font-semibold min-w-0 break-words';
        content.textContent = value;
        line.append(symbol, name, content);
        return line;
    }

    window.volverPaso2 = function() {
        Alpine.store('booking').volverPaso2();
        document.getElementById('hora_inicio').value = '';
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
