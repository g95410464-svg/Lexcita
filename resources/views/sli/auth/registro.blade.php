@extends('layouts.auth')
@section('title', 'Crear cuenta')
@section('content')
<span class="sli-eyebrow">EMPECEMOS POR CONOCERNOS</span>
<h2>Tu cuenta, tu espacio.</h2><p class="sli-auth-intro">Regístrate para agendar y gestionar tus consultas legales.</p>
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
