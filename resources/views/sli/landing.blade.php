@section('title', 'Tu portal de servicios legales')
<!DOCTYPE html>
<html lang="es" data-portal="sli">
<head>
    @include('partials.head')
    <meta name="description" content="El portal de Servicios Legales Integrados S.A. de C.V. en El Salvador. Agenda tus consultas y da seguimiento a tus citas con el despacho.">
</head>
<body class="sli-landing">
<a class="sli-skip" href="#contenido-principal">Saltar al contenido</a>
<header class="sli-landing-header sli-landing-width">
    <a class="sli-landing-brand" href="{{ route('home') }}" aria-label="Servicios Legales Integrados, inicio">
        <img src="{{ asset('images/sli-firma.png') }}" alt="G.C. Tu Conexión Legal · Abogados y Notarios" width="1536" height="585">
    </a>
    <nav aria-label="Navegación principal" class="sli-landing-nav">
        <a class="sli-landing-about" href="#como-funciona">Cómo funciona</a>
        <a class="sli-button sli-button-primary" href="{{ route('login') }}">Iniciar sesión<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
    </nav>
</header>
<main id="contenido-principal" tabindex="-1">
    <section class="sli-landing-hero sli-landing-width" aria-labelledby="landing-title">
        <div class="sli-landing-intro">
            <p class="sli-landing-eyebrow"><span></span>SERVICIOS LEGALES INTEGRADOS</p>
            <h1 id="landing-title">Tu gestión legal,<br><em>más cerca de ti.</em></h1>
            <p class="sli-landing-lead">Un espacio para agendar tus consultas, conectar con tu abogado y dar seguimiento a tus citas.</p>
            <div class="sli-landing-actions">
                <a class="sli-button sli-button-primary" href="{{ route('login') }}">Ingresar al portal<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></a>
                <a class="sli-button sli-button-outline" href="{{ route('registro') }}">Crear mi cuenta</a>
            </div>
            <a class="sli-landing-caption" href="#como-funciona">Conoce cómo funciona<span class="material-symbols-outlined" aria-hidden="true">south</span></a>
        </div>
        <figure class="sli-landing-portrait">
            <img src="{{ asset('images/sli-abogado-retrato.webp') }}" alt="Retrato ilustrativo de un abogado de cabello castaño rojizo en un despacho" width="1122" height="1402" fetchpriority="high">
            <figcaption><span class="material-symbols-outlined" aria-hidden="true">balance</span><p>Tu confianza,<br><strong>nuestra responsabilidad legal.</strong></p></figcaption>
        </figure>
    </section>
    <section class="sli-landing-how" id="como-funciona" aria-labelledby="steps-title">
        <div class="sli-landing-steps sli-landing-width">
            <div class="sli-landing-section-heading"><div><p class="sli-landing-eyebrow">DEL PRIMER PASO A TU CONSULTA</p><h2 id="steps-title">Cómo funciona</h2></div><p>Un proceso sencillo para organizar tus consultas y mantenerte cerca de tu despacho.</p></div>
            <ol>
                <li><div class="sli-landing-step-top"><span class="sli-landing-step-number">01</span><span class="material-symbols-outlined" aria-hidden="true">person_add</span></div><h3>Entra a tu espacio</h3><p>Crea tu cuenta o inicia sesión con tu correo o Google.</p></li>
                <li><div class="sli-landing-step-top"><span class="sli-landing-step-number">02</span><span class="material-symbols-outlined" aria-hidden="true">calendar_month</span></div><h3>Agenda tu consulta</h3><p>Elige un abogado y un horario según la disponibilidad del despacho.</p></li>
                <li><div class="sli-landing-step-top"><span class="sli-landing-step-number">03</span><span class="material-symbols-outlined" aria-hidden="true">forum</span></div><h3>Mantén todo a la vista</h3><p>Revisa tus citas y accede a tu consulta virtual cuando corresponda.</p></li>
            </ol>
        </div>
    </section>
    <section class="sli-landing-contact sli-landing-width" aria-labelledby="contact-title">
        <div><h2 id="contact-title">¿Prefieres hablar con nosotros?</h2><p>El despacho puede orientarte antes de agendar tu consulta.</p></div>
        <a class="sli-button sli-button-outline" href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contactar al despacho<span class="material-symbols-outlined" aria-hidden="true">north_east</span></a>
    </section>
</main>
<footer class="sli-landing-footer sli-landing-width"><p>© {{ now()->year }} Servicios Legales Integrados S.A. de C.V.</p><a href="https://servicioslegalesint.com/" target="_blank" rel="noopener noreferrer">Conoce nuestro despacho <span aria-hidden="true">↗</span></a></footer>
</body>
</html>
