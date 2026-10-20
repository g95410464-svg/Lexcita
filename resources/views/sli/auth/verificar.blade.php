@extends('layouts.auth')
@section('title', 'Verifica tu cuenta')
@section('content')
<span class="sli-eyebrow">UN PASO MÁS PARA CONTINUAR</span>
<h2>Verifiquemos tu cuenta.</h2>
<p class="sli-auth-intro">Enviamos códigos de seis dígitos para confirmar que tienes acceso a los dos medios de contacto.</p>
<form action="{{ route('registro.verificar.post') }}" method="POST" data-submit-once>
    @csrf
    <div class="sli-field"><label for="email_code">Código enviado al correo</label><p class="sli-auth-intro">{{ $contacts['email'] }}</p><input id="email_code" name="email_code" inputmode="numeric" autocomplete="off" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required></div>
    <div class="sli-field"><label for="sms_code">Código enviado por SMS</label><p class="sli-auth-intro">{{ $contacts['phone'] }}</p><input id="sms_code" name="sms_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required></div>
    <p class="sli-auth-intro">Los códigos vencen en 10 minutos. Necesitas confirmar ambos para crear tu cuenta.</p>
    <button class="sli-button sli-button-primary sli-button-wide" type="submit" data-label="Verificar cuenta">Verificar cuenta</button>
</form>
<form action="{{ route('registro.verificar.resend') }}" method="POST" class="mt-4">
    @csrf
    <button class="sli-button sli-button-wide" type="submit">Reenviar códigos</button>
</form>
<p class="sli-auth-switch"><a href="{{ route('registro') }}">Volver al registro</a></p>
@endsection
