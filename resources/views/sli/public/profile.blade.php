@extends('sli.public.layout')
@section('title', $abogado->nombre)
@section('public-content')
<section class="sli-public-profile sli-landing-width">
    <a class="sli-public-back" href="{{ route('sli.lawyers') }}">← Todos los abogados</a>
    <div class="sli-public-profile-grid">
        @if($abogado->perfil_imagen)<img class="sli-lawyer-monogram-large sli-lawyer-photo" src="{{ route('sli.media', $abogado->perfil_imagen) }}" alt="Retrato de {{ $abogado->nombre }}">@else<div class="sli-lawyer-monogram sli-lawyer-monogram-large" aria-hidden="true">{{ mb_substr($abogado->nombre, 0, 1) }}</div>@endif
        <div><p class="sli-landing-eyebrow">ABOGADO / SERVICIOS LEGALES INTEGRADOS</p><h1>{{ $abogado->nombre }}</h1><h2>Especialidad</h2><p>{{ $abogado->especialidad ?: 'Contacta al despacho para conocer sus áreas de atención.' }}</p>@if($abogado->perfil_biografia)<h2>Perfil profesional</h2><p>{{ $abogado->perfil_biografia }}</p>@endif<hr><h2>Tu próxima consulta</h2><p>Elige una fecha y un horario disponible. Podrás revisar los detalles antes de confirmar tu cita.</p><a class="sli-button sli-button-primary" href="{{ route('sli.book', $abogado->id) }}">Agendar con este abogado <span aria-hidden="true">↗</span></a>@guest<p class="sli-public-note">Primero crea tu cuenta con correo o Google. Si ya tienes una, puedes iniciar sesión.</p>@endguest</div>
    </div>
</section>
@endsection
