@extends('layouts.auth')
@section('title', 'Espera un momento')
@section('content')
<span class="sli-eyebrow">HAGAMOS UNA PAUSA</span><h2>Espera un momento.</h2><p class="sli-auth-intro" role="alert">{{ $message }}</p><p class="sli-auth-intro">Cuando termine la espera, vuelve a la página anterior e inténtalo otra vez.</p><a class="sli-button sli-button-primary" href="{{ route('login') }}">Ir al inicio de sesión</a>
@endsection