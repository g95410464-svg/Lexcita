<!DOCTYPE html>
<html lang="es" data-portal="sli">
<head>
    @include('partials.head')
    <meta name="description" content="El portal de Servicios Legales Integrados S.A. de C.V. en El Salvador. Agenda tus consultas y da seguimiento a tus citas con el despacho.">
    @php
        $theme = $sliSiteContent['theme'];
        $validColor = static fn ($value, $fallback) => is_string($value) && preg_match('/\A#[0-9a-fA-F]{6}\z/', $value) ? $value : $fallback;
    @endphp
    <style>:root{--navy:{{ $validColor($theme['navy'] ?? null, '#1d2a44') }};--ink:var(--navy);--gold:{{ $validColor($theme['gold'] ?? null, '#977045') }};--canvas:{{ $validColor($theme['canvas'] ?? null, '#f3efe7') }};--sli-sage:{{ $validColor($theme['sage'] ?? null, '#e2e8e4') }}}.sli-landing{background:var(--canvas)}.sli-landing-how,.sli-public-cover{background:var(--sli-sage)}</style>
</head>
<body class="sli-landing">
<a class="sli-skip" href="#contenido-principal">Saltar al contenido</a>
<header class="sli-landing-header sli-landing-width">
    <a class="sli-landing-brand" href="{{ route('home') }}" aria-label="Servicios Legales Integrados, inicio">
        <img src="{{ $sliSiteContent['home']['logo'] ? route('sli.media', $sliSiteContent['home']['logo']) : asset('images/sli-firma.png') }}" alt="G.C. Tu Conexión Legal · Abogados y Notarios" width="1536" height="585">
    </a>
    <nav aria-label="Navegación principal" class="sli-landing-nav">
        @foreach(['home' => 'Inicio', 'sli.about' => 'Conócenos', 'sli.lawyers' => 'Nuestros abogados', 'sli.how' => 'Cómo funciona'] as $name => $label)
        <a class="sli-public-nav-link" href="{{ route($name) }}" @if(request()->routeIs($name) || ($name === 'sli.lawyers' && request()->routeIs('sli.profile'))) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        <a class="sli-button sli-button-primary" href="{{ route('login') }}">Iniciar sesión<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
    </nav>
</header>
<main id="contenido-principal" tabindex="-1">
@yield('public-content')
</main>
<footer class="sli-landing-footer sli-landing-width"><p>© {{ now()->year }} Servicios Legales Integrados S.A. de C.V.</p><a href="https://servicioslegalesint.com/" target="_blank" rel="noopener noreferrer">Conoce nuestro despacho <span aria-hidden="true">↗</span></a></footer>
</body>
</html>
