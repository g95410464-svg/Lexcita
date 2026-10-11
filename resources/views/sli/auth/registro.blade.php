@extends('layouts.auth')
@section('title', 'Crear cuenta')
@section('content')
<span class="sli-eyebrow">EMPECEMOS POR CONOCERNOS</span>
<h2>Tu cuenta, tu espacio.</h2><p class="sli-auth-intro">Regístrate para agendar y gestionar tus consultas legales.</p>
<a class="sli-google-button" href="{{ route('google.redirect') }}"><svg width="19" height="19" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285f4" d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.03v2.52h3.24c1.89-1.74 2.98-4.3 2.98-7.38Z"/><path fill="#34a853" d="M12 22c2.7 0 4.96-.9 6.62-2.39l-3.24-2.52c-.9.6-2.05.97-3.38.97-2.6 0-4.8-1.76-5.59-4.13H3.07v2.59A10 10 0 0 0 12 22Z"/><path fill="#fbbc05" d="M6.41 13.93a6 6 0 0 1 0-3.86V7.48H3.07a10 10 0 0 0 0 9.04l3.34-2.59Z"/><path fill="#ea4335" d="M12 5.94c1.47 0 2.79.5 3.83 1.5l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.93 5.48l3.34 2.59A5.97 5.97 0 0 1 12 5.94Z"/></svg>Continuar con Google</a>
<div class="sli-divider"><span>o crea tu cuenta con correo</span></div>
<form action="{{ route('registro.post') }}" method="POST" data-submit-once>
@csrf
<div class="sli-field"><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" autocomplete="name" value="{{ old('nombre') }}" maxlength="120" placeholder="Tu nombre y apellido" required></div>
<div class="sli-field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" maxlength="255" placeholder="nombre@correo.com" required @error('email') aria-invalid="true" @enderror></div>
<div class="sli-field"><label for="telefono_whatsapp">Teléfono de WhatsApp</label><input id="telefono_whatsapp" name="telefono_whatsapp" type="tel" autocomplete="tel" value="{{ old('telefono_whatsapp') }}" maxlength="20" placeholder="+503 7000 0000" required></div>
<div class="sli-form-columns"><div class="sli-field"><label for="password">Contraseña</label><div class="sli-password"><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="Mínimo 8 caracteres" aria-describedby="password-help" required><button type="button" data-toggle-password="password" aria-label="Mostrar contraseña" aria-pressed="false"><span class="material-symbols-outlined" aria-hidden="true">visibility</span></button></div></div>
<div class="sli-field"><label for="password_confirmation">Repite la contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" placeholder="Confirma tu contraseña" required></div></div>
<p id="password-help" class="sli-field-hint">Usa al menos 8 caracteres. Combina letras, números y símbolos.</p>
<button class="sli-button sli-button-primary sli-button-wide" type="submit" data-label="Crear mi cuenta">Crear mi cuenta</button>
</form>
<p class="sli-auth-switch">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Inicia sesión <span aria-hidden="true">↗</span></a></p>
@endsection
