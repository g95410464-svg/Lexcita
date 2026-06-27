@extends('layouts.app')
@section('title', 'Pago Cancelado')

@section('content')
<div style="max-width:520px;margin:0 auto;text-align:center;padding-top:40px;">
    <div style="width:72px;height:72px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#991B1B" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;margin-bottom:8px;">Pago cancelado</h1>
    <p style="color:#6B6B6B;margin-bottom:24px;">No se realizó ningún cargo. Tu cita quedó en estado "Pendiente de pago".</p>
    <div style="display:flex;gap:12px;justify-content:center;">
        <a href="{{ route('cliente.mis-citas') }}" class="btn btn-primary">Mis citas</a>
        <a href="{{ route('cliente.nueva-cita') }}" class="btn btn-outline">Intentar de nuevo</a>
    </div>
</div>
@endsection
