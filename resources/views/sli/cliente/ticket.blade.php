@extends('layouts.app')
@section('title', 'Comprobante de cita')
@section('content')
<div class="sli-document"><div class="sli-page-heading sli-no-print"><div><span class="sli-eyebrow">LOS DETALLES, A TU MANO</span><h1>Tu comprobante de cita.</h1><p>Guárdalo para consultar los datos de tu atención.</p></div></div>
<article class="sli-document-card"><div class="sli-document-title"><img src="{{ asset('images/sli-firma.png') }}" class="sli-document-logo" alt="G.C. Tu Conexión Legal"><div><h2>Servicios Legales Integrados</h2><p>S.A. de C.V. · El Salvador</p></div></div>
<div class="flex justify-between gap-4 items-center mb-8"><div><span class="sli-eyebrow">CLIENTE</span><p class="mt-2 font-semibold">{{ auth()->user()->nombre }}</p></div><span class="sli-status sli-status-{{ $cita->estado }}">{{ str_replace('_', ' ', ucfirst($cita->estado)) }}</span></div>
@include('cliente.detalle-cita')
<div class="sli-payment-total"><span>Monto de la consulta<small>USD · Dólares estadounidenses</small></span><strong>${{ number_format($cita->monto, 2) }}</strong></div>
<p class="sli-auth-help">Este comprobante detalla tu cita y su estado actual. Para realizar cambios o resolver dudas, comunícate con el despacho.</p></article>
<div class="flex gap-3 flex-wrap sli-no-print"><button type="button" class="sli-button sli-button-primary" onclick="window.print()"><span class="material-symbols-outlined" aria-hidden="true">print</span>Imprimir comprobante</button><a class="sli-button sli-button-outline" href="{{ route('cliente.mis-citas') }}">Volver a mis citas</a></div></div>
@endsection
