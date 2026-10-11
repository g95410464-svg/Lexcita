@extends('sli.public.layout')
@section('title', 'Cómo funciona')
@section('public-content')
    <section class="sli-landing-how" id="como-funciona" aria-labelledby="steps-title">
        <div class="sli-landing-steps sli-landing-width">
            <div class="sli-landing-section-heading"><div><p class="sli-landing-eyebrow">DEL PRIMER PASO A TU CONSULTA</p><h1 id="steps-title">Cómo funciona</h1></div><p>Un proceso sencillo para organizar tus consultas y mantenerte cerca de tu despacho.</p></div>
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
@endsection
