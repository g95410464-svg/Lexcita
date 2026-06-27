@extends('layouts.app')
@section('title', 'Pago Confirmado')

@section('content')
<div style="max-width:520px;margin:0 auto;text-align:center;padding-top:40px;">
    <div style="width:72px;height:72px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#065F46" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:8px;">¡Cita confirmada!</h1>
    <p style="color:#6B6B6B;margin-bottom:24px;">Tu pago fue procesado correctamente. Recibirás un mensaje de WhatsApp con los detalles.</p>

    @if(isset($cita))
    <div class="card" style="text-align:left;margin-bottom:24px;">
        <div style="font-size:.88rem;color:#6B6B6B;margin-bottom:4px;">Código de cita</div>
        <div style="font-family:monospace;font-size:1.1rem;font-weight:700;color:#C9A84C;margin-bottom:16px;">{{ $cita->codigo }}</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.9rem;">
            <div><span style="color:#6B6B6B;">Abogado</span><br><strong>{{ $cita->abogado->nombre }}</strong></div>
            <div><span style="color:#6B6B6B;">Fecha</span><br><strong>{{ $cita->fecha->format('d/m/Y') }}</strong></div>
            <div><span style="color:#6B6B6B;">Hora</span><br><strong>{{ $cita->hora_inicio }}</strong></div>
            <div><span style="color:#6B6B6B;">Modalidad</span><br><strong>{{ ucfirst($cita->modalidad) }}</strong></div>
        </div>
    </div>
    @endif

    <a href="{{ route('cliente.mis-citas') }}" class="btn btn-primary">Ver mis citas</a>
</div>
@endsection
