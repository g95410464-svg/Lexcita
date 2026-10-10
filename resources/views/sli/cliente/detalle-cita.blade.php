<dl class="sli-detail-grid">
<div><dt>Código de cita</dt><dd>{{ $cita->codigo }}</dd></div><div><dt>Abogado</dt><dd>{{ $cita->abogado->nombre }}</dd></div>
<div><dt>Fecha</dt><dd>{{ $cita->fecha->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</dd></div><div><dt>Horario</dt><dd>{{ substr($cita->hora_inicio, 0, 5) }} – {{ substr($cita->hora_fin, 0, 5) }}</dd></div>
<div><dt>Tipo de consulta</dt><dd>{{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}</dd></div><div><dt>Modalidad</dt><dd>{{ ucfirst($cita->modalidad) }}</dd></div>
</dl>
