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
            <p class="sli-landing-caption">Tu despacho. Tus consultas. Un mismo lugar.</p>
        </div>
        <aside class="sli-landing-promise" aria-label="Nuestro compromiso">
            <p class="sli-landing-promise-label">TU CONEXIÓN LEGAL · EL SALVADOR</p>
            <svg class="sli-landing-emblem" viewBox="0 0 240 180" fill="none" aria-hidden="true">
                <path d="M28 166V97a92 92 0 0 1 184 0v69M42 166V97a78 78 0 0 1 156 0v69M56 166V97a64 64 0 0 1 128 0v69" stroke="currentColor" opacity=".2"/>
                <g stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M120 49v91m-22 5h44m-16-80 38 9m-50-9-38 9M80 79l-16 35h32L80 79Zm80 0-16 35h32l-16-35Z"/>
                    <path d="M64 114c2 15 30 15 32 0m48 0c2 15 30 15 32 0"/>
                    <circle cx="120" cy="62" r="6" fill="#1d2a44"/>
                </g>
            </svg>
            <h2>Tu confianza,<br><em>nuestra responsabilidad legal.</em></h2>
            <div class="sli-landing-promise-footer"><span>Servicios Legales Integrados<br>S.A. de C.V.</span><span class="material-symbols-outlined" aria-hidden="true">north_east</span></div>
        </aside>
    </section>
    <section class="sli-landing-steps sli-landing-width" id="como-funciona" aria-labelledby="steps-title">
        <div class="sli-landing-section-heading"><p class="sli-landing-eyebrow">ASÍ DE SENCILLO</p><h2 id="steps-title">El siguiente paso, a tu alcance.</h2></div>
        <ol>
            <li><span class="sli-landing-step-number">01</span><div><h3>Entra a tu espacio</h3><p>Crea tu cuenta o inicia sesión con tu correo o Google.</p></div></li>
            <li><span class="sli-landing-step-number">02</span><div><h3>Agenda tu consulta</h3><p>Elige un abogado y un horario según la disponibilidad del despacho.</p></div></li>
            <li><span class="sli-landing-step-number">03</span><div><h3>Mantén todo a la vista</h3><p>Revisa tus citas y accede a tu consulta virtual cuando corresponda.</p></div></li>
        </ol>
    </section>
    <section class="sli-landing-contact sli-landing-width" aria-labelledby="contact-title">
        <div><h2 id="contact-title">¿Prefieres hablar con nosotros?</h2><p>El despacho puede orientarte antes de agendar tu consulta.</p></div>
        <a class="sli-button sli-button-outline" href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contactar al despacho<span class="material-symbols-outlined" aria-hidden="true">north_east</span></a>
    </section>
</main>
<footer class="sli-landing-footer sli-landing-width"><p>© {{ now()->year }} Servicios Legales Integrados S.A. de C.V.</p><a href="https://servicioslegalesint.com/" target="_blank" rel="noopener noreferrer">Conoce nuestro despacho <span aria-hidden="true">↗</span></a></footer>
</body>
</html>
