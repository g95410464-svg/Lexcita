@extends('sli.public.layout')
@section('title', 'Tu portal de servicios legales')
@section('public-content')
    <section class="sli-landing-hero sli-landing-width" aria-labelledby="landing-title">
        <div class="sli-landing-intro">
            <p class="sli-landing-eyebrow"><span></span>{{ $sliSiteContent['home']['eyebrow'] }}</p>
            <h1 id="landing-title">{{ $sliSiteContent['home']['title'] }}</h1>
            <p class="sli-landing-lead">{{ $sliSiteContent['home']['description'] }}</p>
            <div class="sli-landing-actions">
                <a class="sli-button sli-button-primary" href="{{ route('sli.start') }}">Agendar una cita<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                <a class="sli-button sli-button-outline" href="{{ route('sli.lawyers') }}">Conocer a los abogados</a>
            </div>
            <a class="sli-landing-caption" href="{{ route('sli.how') }}">Conoce cómo funciona<span class="material-symbols-outlined" aria-hidden="true">south</span></a>
        </div>
        <figure class="sli-landing-portrait">
            <img src="{{ $sliSiteContent['home']['image'] ? route('sli.media', $sliSiteContent['home']['image']) : asset('images/sli-abogado-retrato.webp') }}" alt="Retrato del despacho Servicios Legales Integrados" width="1122" height="1402" fetchpriority="high">
            <figcaption><span class="material-symbols-outlined" aria-hidden="true">balance</span><p>Tu confianza,<br><strong>nuestra responsabilidad legal.</strong></p></figcaption>
        </figure>
    </section>
<section class="sli-public-paths sli-landing-width" aria-label="Explora el despacho">
<a href="{{ route('sli.about') }}"><span>01 / EL DESPACHO</span><h2>Conócenos <span aria-hidden="true">↗</span></h2><p>Un espacio dedicado a nuestra firma y a la manera de acompañarte.</p></a>
<a href="{{ route('sli.lawyers') }}"><span>02 / NUESTRO EQUIPO</span><h2>Encuentra a tu abogado <span aria-hidden="true">↗</span></h2><p>Consulta los perfiles y especialidades antes de elegir con quién agendar.</p></a>
</section>
    <section class="sli-landing-contact sli-landing-width" aria-labelledby="contact-title">
        <div><h2 id="contact-title">¿Prefieres hablar con nosotros?</h2><p>El despacho puede orientarte antes de agendar tu consulta.</p></div>
        <a class="sli-button sli-button-outline" href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contactar al despacho<span class="material-symbols-outlined" aria-hidden="true">north_east</span></a>
    </section>
@endsection
