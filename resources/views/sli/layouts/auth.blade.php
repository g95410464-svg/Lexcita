<!DOCTYPE html>
<html lang="es" data-portal="sli">
<head>@include('partials.head')</head>
<body class="sli-auth">
<a class="sli-skip" href="#contenido-principal">Saltar al contenido</a>
<section class="sli-auth-story" aria-label="Servicios Legales Integrados">
    <a class="sli-brand" href="{{ route('home') }}" aria-label="Servicios Legales Integrados, inicio">@include('partials.brand')</a>
    <div class="sli-story-content"><span class="sli-eyebrow">TU PORTAL DE SERVICIOS LEGALES</span><h1>Tu confianza,<br><em>nuestra responsabilidad legal.</em></h1><p>Un espacio para conectar con tu abogado y dar seguimiento a tus consultas, estés donde estés.</p>
    <div class="sli-story-steps"><div><span>01</span><p>Elige a tu abogado</p></div><div><span>02</span><p>Agenda tu consulta</p></div><div><span>03</span><p>Conéctate y avanza</p></div></div></div>
    <div class="sli-architecture" aria-hidden="true"><span></span><span></span><span></span></div>
    <p class="sli-story-footer">SERVICIOS LEGALES INTEGRADOS S.A. DE C.V. <span>EL SALVADOR</span></p>
</section>
<main class="sli-auth-form-side" id="contenido-principal">
    <div class="sli-auth-top"><span>PORTAL SLI</span><a href="{{ route('home') }}">← Volver al inicio</a></div>
    <div class="sli-auth-form">
        @if($errors->any())<div class="sli-alert sli-alert-error" role="alert"><span class="material-symbols-outlined" aria-hidden="true">error</span><div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div></div>@endif
        @if(session('status'))<div class="sli-alert sli-alert-success" role="status">{{ session('status') }}</div>@endif
        @yield('content')
    </div>
    <footer class="sli-auth-bottom"><span class="material-symbols-outlined" aria-hidden="true">balance</span><p>Servicios Legales Integrados S.A. de C.V.<br><span>© {{ now()->year }} · Todos los derechos reservados.</span></p></footer>
</main>
@stack('scripts')
</body></html>
