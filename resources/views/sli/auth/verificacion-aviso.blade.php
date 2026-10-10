@extends('layouts.auth')
@section('title', 'Verificación de correo')
@section('content')
<span class="sli-eyebrow">TU CUENTA SLI</span><h2>Verifica tu correo.</h2><p class="sli-auth-intro">Si recibiste un enlace de verificación, ábrelo para continuar. Revisa también la carpeta de correo no deseado.</p><a class="sli-button sli-button-primary" href="{{ route('login') }}">Volver al inicio de sesión</a>
@endsection