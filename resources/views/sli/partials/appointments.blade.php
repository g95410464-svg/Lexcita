@forelse($appointments as $cita)
<div class="sli-appointment">
    <div class="sli-date"><span>{{ $cita->fecha->locale('es')->isoFormat('MMM') }}</span><strong>{{ $cita->fecha->format('d') }}</strong></div>
    <div class="sli-appointment-info"><strong>{{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}</strong><p>{{ auth()->user()->esCliente() ? $cita->abogado->nombre : $cita->cliente->nombre }}</p><p>{{ substr($cita->hora_inicio, 0, 5) }} · {{ ucfirst($cita->modalidad) }}</p>
        @if($cita->esVirtual() && $cita->videoRoom)<a href="{{ route('video.sala', $cita->videoRoom->room_token) }}"><span class="material-symbols-outlined" aria-hidden="true">videocam</span>Ir a videollamada</a>@endif
    </div>
    <span class="sli-status sli-status-{{ $cita->estado }}">{{ ucfirst(str_replace('_', ' ', $cita->estado)) }}</span>
</div>
@empty
<div class="sli-empty"><div class="sli-empty-icon"><span class="material-symbols-outlined" aria-hidden="true">event_available</span></div><h3>{{ $emptyTitle ?? 'Tu agenda tiene espacio' }}</h3><p>{{ $emptyMessage ?? 'Las consultas confirmadas aparecerán aquí con todos sus detalles.' }}</p>
    @if(auth()->user()->esCliente())<a class="sli-button sli-button-outline" href="{{ route('cliente.nueva-cita') }}">Agendar una consulta<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>@endif
</div>
@endforelse
