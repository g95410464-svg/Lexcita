@extends('layouts.app')
@section('title', 'Mi Agenda')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
    /* Override FullCalendar para tema oscuro Laureti */
    .fc { font-family: 'Hanken Grotesk', sans-serif; }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid { border-color: #444748; }
    .fc .fc-daygrid-day { background: #1f201e; }
    .fc .fc-daygrid-day:hover { background: #292a29; }
    .fc .fc-daygrid-day.fc-day-today { background: rgba(233,195,73,0.06); }
    .fc .fc-col-header-cell { background: #0d0f0d; color: #8e9192; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .fc .fc-daygrid-day-number { color: #c4c7c7; font-size: 13px; }
    .fc .fc-day-today .fc-daygrid-day-number { color: #e9c349; font-weight: 700; }
    .fc .fc-button-primary { background: transparent; border-color: #444748; color: #c4c7c7; font-family: 'Hanken Grotesk', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; border-radius: 0; }
    .fc .fc-button-primary:hover { background: #292a29; border-color: #e9c349; color: #e9c349; }
    .fc .fc-button-primary:not(:disabled):active, .fc .fc-button-primary:not(:disabled).fc-button-active { background: rgba(233,195,73,.12); border-color: #e9c349; color: #e9c349; }
    .fc .fc-toolbar-title { font-family: 'Libre Caslon Text', serif; font-size: 20px; color: #e3e2e0; }
    .fc-event { border-radius: 0; border: none; font-size: 12px; }
    .fc-daygrid-dot-event .fc-event-title { color: #1f201e; }
</style>
@endpush

@section('content')

<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Portal del Abogado</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Mi Agenda</h1>
    <p class="text-outline mt-1 text-sm">Visualiza todas tus citas del mes.</p>
</div>

<div class="bg-surface-container border border-outline-variant p-6">
    <div id="calendario"></div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var cal = new FullCalendar.Calendar(document.getElementById('calendario'), {
        initialView: 'dayGridMonth',
        locale: 'es',
        height: 'auto',
        events: @json($citas),
        eventColor: '#e9c349',
        eventTextColor: '#3c2f00',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek'
        },
        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana' },
        eventClick: function(info) {
            alert('Cita: ' + info.event.title);
        }
    });
    cal.render();
});
</script>
@endpush
