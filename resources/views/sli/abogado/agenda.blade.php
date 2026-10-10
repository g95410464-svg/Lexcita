@extends('layouts.app')
@section('title', 'Mi Agenda')

@push('styles')
<style>
    /* Calendario institucional de SLI */
    .fc { font-family: 'Hanken Grotesk', sans-serif; }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid { border-color: #d7e0e8; }
    .fc .fc-daygrid-day { background: #ffffff; }
    .fc .fc-daygrid-day:hover { background: #f0f3f7; }
    .fc .fc-daygrid-day.fc-day-today { background: rgba(23,59,89,0.06); }
    .fc .fc-col-header-cell { background: #f7f8fa; color: #67788b; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .fc .fc-daygrid-day-number { color: #536477; font-size: 13px; }
    .fc .fc-day-today .fc-daygrid-day-number { color: #173b59; font-weight: 700; }
    .fc .fc-button-primary { background: transparent; border-color: #d7e0e8; color: #536477; font-family: 'Hanken Grotesk', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; border-radius: 0; }
    .fc .fc-button-primary:hover { background: #f0f3f7; border-color: #173b59; color: #173b59; }
    .fc .fc-button-primary:not(:disabled):active, .fc .fc-button-primary:not(:disabled).fc-button-active { background: rgba(23,59,89,.12); border-color: #173b59; color: #173b59; }
    .fc .fc-toolbar-title { font-family: 'Bricolage Grotesque', sans-serif; font-size: 20px; color: #152e46; }
    .fc-event { border-radius: 0; border: none; font-size: 12px; }
    .fc-daygrid-dot-event .fc-event-title { color: #152e46; }
</style>
@endpush

@section('content')

<div class="mb-8">
    <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Portal del Abogado</p>
    <h1 class="font-caslon text-4xl font-normal text-on-surface">Mi Agenda</h1>
    <p class="text-outline mt-1 text-sm">Visualiza todas tus citas del mes.</p>
</div>

<div class="bg-surface-container border border-outline-variant p-3 sm:p-6">
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
        eventColor: '#173b59',
        eventTextColor: '#ffffff',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek'
        },
        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana' },
        // Marca con un ícono de cámara las citas virtuales confirmadas que ya
        // tienen sala creada, para indicar que se puede entrar a la videollamada.
        eventContent: function(arg) {
            var url = arg.event.extendedProps.url_sala;
            var cont = document.createElement('div');
            cont.style.cssText = 'display:flex;align-items:center;gap:4px;width:100%;overflow:hidden;';
            var text = document.createElement('span');
            text.textContent = arg.event.title;
            text.style.cssText = 'flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
            cont.appendChild(text);
            if (url) {
                var icon = document.createElement('span');
                icon.textContent = '📹';
                icon.style.cssText = 'flex-shrink:0;font-size:11px;';
                icon.title = 'Unirse a videollamada';
                cont.appendChild(icon);
            }
            return { domNodes: [cont] };
        },
        eventClick: function(info) {
            var url = info.event.extendedProps.url_sala;
            if (url) {
                // Abre la MISMA sala (video.sala) en una pestaña nueva.
                window.open(url, '_blank', 'noopener');
            } else {
                alert('Cita: ' + info.event.title);
            }
        }
    });
    cal.render();
});
</script>
@endpush