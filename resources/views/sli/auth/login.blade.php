@extends('layouts.auth')
@section('title', 'Iniciar sesión')
@section('content')
<span class="sli-eyebrow">BIENVENIDO A TU PORTAL</span>
<h2>Continuemos juntos.</h2>
<p class="sli-auth-intro">Ingresa a tu cuenta para gestionar tus citas y consultas.</p>
<a class="sli-google-button" href="{{ route('google.redirect') }}"><svg width="19" height="19" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285f4" d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.03v2.52h3.24c1.89-1.74 2.98-4.3 2.98-7.38Z"/><path fill="#34a853" d="M12 22c2.7 0 4.96-.9 6.62-2.39l-3.24-2.52c-.9.6-2.05.97-3.38.97-2.6 0-4.8-1.76-5.59-4.13H3.07v2.59A10 10 0 0 0 12 22Z"/><path fill="#fbbc05" d="M6.41 13.93a6 6 0 0 1 0-3.86V7.48H3.07a10 10 0 0 0 0 9.04l3.34-2.59Z"/><path fill="#ea4335" d="M12 5.94c1.47 0 2.79.5 3.83 1.5l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.93 5.48l3.34 2.59A5.97 5.97 0 0 1 12 5.94Z"/></svg>Continuar con Google</a>
<div class="sli-divider"><span>o ingresa con tu correo</span></div>
<form action="{{ route('login.post') }}" method="POST" data-submit-once>
@csrf
<div class="sli-field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="nombre@correo.com" required @error('email') aria-invalid="true" @enderror></div>
<div class="sli-field"><label for="password">Contraseña</label><div class="sli-password"><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" required><button type="button" data-toggle-password="password" aria-label="Mostrar contraseña" aria-pressed="false"><span class="material-symbols-outlined" aria-hidden="true">visibility</span></button></div></div>
<button class="sli-button sli-button-primary sli-button-wide" type="submit" data-label="Iniciar sesión">Iniciar sesión</button>
</form>
<p class="sli-auth-switch">¿Es tu primera visita? <a href="{{ route('registro') }}">Crea tu cuenta <span aria-hidden="true">↗</span></a></p>
<p class="sli-auth-help">¿Necesitas ayuda con tu acceso? <a href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contacta al despacho</a>.</p>
@endsection
