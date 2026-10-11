@extends('sli.public.layout')
@section('title', 'Conócenos')
@section('public-content')
<section class="sli-public-cover">
    <div class="sli-landing-width"><p class="sli-landing-eyebrow">CONÓCENOS / SERVICIOS LEGALES INTEGRADOS</p><h1>Tu confianza merece<br>un espacio cercano.</h1><p>Servicios Legales Integrados S.A. de C.V.<br>Tu Conexión Legal · Abogados y Notarios · El Salvador.</p></div>
</section>
<section class="sli-public-story sli-landing-width" aria-labelledby="about-title">
    <div><p class="sli-landing-eyebrow">NUESTRO DESPACHO</p><h2 id="about-title">La atención empieza<br>por conocernos.</h2></div>
    <div><p>Este portal te permite conocer a los abogados del despacho, consultar su especialidad y organizar una cita según su disponibilidad.</p><p>Antes de agendar, explora nuestro equipo. Si necesitas orientación sobre tu consulta, puedes contactar directamente al despacho.</p><a class="sli-button sli-button-primary" href="{{ route('sli.lawyers') }}">Conoce a nuestros abogados <span aria-hidden="true">↗</span></a></div>
</section>
<section class="sli-landing-contact sli-landing-width"><div><h2>Conoce más sobre nuestra firma</h2><p>Visita el sitio institucional de Servicios Legales Integrados.</p></div><a class="sli-button sli-button-outline" href="https://servicioslegalesint.com/" target="_blank" rel="noopener noreferrer">Visitar el sitio del despacho ↗</a></section>
@endsection
