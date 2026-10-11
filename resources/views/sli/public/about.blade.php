@extends('sli.public.layout')
@section('title', 'Conócenos')
@section('public-content')
<section class="sli-public-cover">
    <div class="sli-landing-width"><p class="sli-landing-eyebrow">{{ $sliSiteContent['about']['eyebrow'] }}</p><h1>{{ $sliSiteContent['about']['title'] }}</h1><p>{{ $sliSiteContent['about']['summary'] }}</p></div>
</section>
<section class="sli-public-story sli-landing-width" aria-labelledby="about-title">
    <div><p class="sli-landing-eyebrow">NUESTRO DESPACHO</p><h2 id="about-title">{{ $sliSiteContent['about']['heading'] }}</h2></div>
    <div><p>{{ $sliSiteContent['about']['paragraph_one'] }}</p><p>{{ $sliSiteContent['about']['paragraph_two'] }}</p><a class="sli-button sli-button-primary" href="{{ route('sli.lawyers') }}">Conoce a nuestros abogados <span aria-hidden="true">↗</span></a></div>
</section>
<section class="sli-landing-contact sli-landing-width"><div><h2>Conoce más sobre nuestra firma</h2><p>Visita el sitio institucional de Servicios Legales Integrados.</p></div><a class="sli-button sli-button-outline" href="https://servicioslegalesint.com/" target="_blank" rel="noopener noreferrer">Visitar el sitio del despacho ↗</a></section>
@endsection
