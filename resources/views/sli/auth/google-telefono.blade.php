@extends('layouts.auth')
@section('title', 'Verifica tu teléfono')
@section('content')
<span class="sli-eyebrow">REGISTRO CON GOOGLE</span>
<h2>Confirma tu teléfono.</h2>
<p class="sli-auth-intro">Para proteger tu cuenta y habilitar las citas, escribe tu teléfono con código de país. También enviaremos un código a tu correo de Google.</p>
<form action="{{ route('registro.google.telefono.post') }}" method="POST" data-submit-once>
    @csrf
    <div class="sli-field"><label for="telefono_whatsapp">Teléfono móvil</label><input id="telefono_whatsapp" name="telefono_whatsapp" type="tel" autocomplete="tel" value="{{ old('telefono_whatsapp') }}" maxlength="20" placeholder="+503 7123 4567" required></div>
    <button class="sli-button sli-button-primary sli-button-wide" type="submit" data-label="Enviar códigos">Enviar códigos de verificación</button>
</form>
@endsection
