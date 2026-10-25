@extends('layouts.app')
@section('title', 'Confirmar consulta')
@section('content')
<div class="sli-document">
    <div class="sli-page-heading"><div><span class="sli-eyebrow">UN PASO MÁS</span><h1>Confirma tu consulta.</h1><p>Revisa los detalles y completa el pago para confirmar tu cita.</p></div></div>
    <div class="sli-document-card"><span class="sli-status sli-status-pendiente_pago">Pendiente de pago</span><h2 class="text-2xl font-caslon mt-5 mb-6">Detalles de tu consulta</h2>@include('cliente.detalle-cita')<div class="sli-payment-total"><span>Total a pagar <small>USD · Dólares estadounidenses</small></span><strong>${{ number_format($cita->monto, 2) }}</strong></div></div>
    <div class="sli-document-card"><h2 class="text-xl font-caslon mb-4">Completa tu pago</h2>
        @if($cita->hold_expires_at)
            <div id="payment-hold-countdown" class="sli-note mb-4" data-expires="{{ $cita->hold_expires_at->toIso8601String() }}" role="status" aria-live="polite"></div>
        @endif
        <div id="paypal-mensaje" role="status" class="text-sm mb-4"></div><div id="paypal-error" role="alert" class="sli-alert sli-alert-error hidden"></div>
        @if(config('services.paypal.client_id'))
            <div id="paypal-button-container"></div><p class="sli-auth-help">Pago procesado por PayPal.</p><a class="sli-text-link" href="{{ route('cliente.paypal-pago', $cita->id) }}">Abrir la página completa de PayPal</a>
        @else
            <div class="sli-note"><h3>El pago en línea aún no está disponible</h3><p>Contacta al despacho para recibir indicaciones. Tu cita permanece pendiente de confirmación.</p><a class="sli-text-link" href="https://servicioslegalesint.com/contacto/" target="_blank" rel="noopener noreferrer">Contactar al despacho</a></div>
        @endif
    </div><a class="sli-button sli-button-outline" href="{{ route('cliente.mis-citas') }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>Volver a mis citas</a>
</div>
@endsection
@push('scripts')
@if(config('services.paypal.client_id'))
<script src="https://www.paypal.com/sdk/js?client-id={{ urlencode(config('services.paypal.client_id')) }}&currency=USD&components=buttons"></script>
<script>
    (function () {
        var CITA_ID = {{ $cita->id }};
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var msj = document.getElementById('paypal-mensaje');
        var err = document.getElementById('paypal-error');
        var holdCountdown = document.getElementById('payment-hold-countdown');

        if (holdCountdown) {
            var expiry = new Date(holdCountdown.dataset.expires).getTime();
            var tick = function () {
                var seconds = Math.max(0, Math.ceil((expiry - Date.now()) / 1000));
                var remaining = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
                holdCountdown.textContent = seconds
                    ? 'Este horario está apartado para ti durante ' + remaining + '. Completa el pago antes de que venza.'
                    : 'La reserva venció y el horario volvió a estar disponible. No inicies el pago; agenda otra cita.';
                if (!seconds) holdCountdown.classList.add('sli-alert', 'sli-alert-error');
            };
            tick();
            window.setInterval(tick, 1000);
        }

        function mostrarMensaje(texto) { if (msj) msj.textContent = texto; }
        function mostrarError(texto) {
            if (err) { err.textContent = texto; err.classList.remove('hidden'); }
        }
        function ocultarError() { if (err) err.classList.add('hidden'); }

        function headers() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            };
        }

        async function crearOrden() {
            ocultarError();
            mostrarMensaje('Procesando pago...');
            try {
                var res = await fetch('{{ route('cliente.paypal.create', $cita->id) }}', {
                    method: 'POST',
                    headers: headers(),
                });
                var data = await res.json();
                if (!res.ok || !data.orderID) {
                    mostrarError(data.message || 'No se pudo iniciar el pago con PayPal. Intenta nuevamente.');
                    throw new Error('createOrder falló');
                }
                return data.orderID;
            } catch (e) {
                mostrarError('No se pudo iniciar el pago con PayPal. Intenta nuevamente.');
                throw e;
            }
        }

        async function capturarOrden(orderID) {
            try {
                var res = await fetch('{{ route('cliente.paypal.capture', $cita->id) }}', {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify({ orderID: orderID }),
                });
                var data = await res.json();
                if (!res.ok || !data.success) {
                    mostrarError(data.message || 'No se pudo completar el pago con PayPal. Intenta nuevamente.');
                    return null;
                }
                return data;
            } catch (e) {
                mostrarError('No se pudo completar el pago con PayPal. Intenta nuevamente.');
                return null;
            }
        }

        if (typeof paypal !== 'undefined' && paypal.Buttons) {
            paypal.Buttons({
                style: { layout: 'vertical', shape: 'rect', color: 'gold', label: 'paypal' },
                createOrder: crearOrden,
                onApprove: async function (data) {
                    mostrarMensaje('Procesando pago...');
                    var result = await capturarOrden(data.orderID);
                    if (result && result.redirect) {
                        window.location.href = result.redirect;
                    }
                },
                onCancel: function () {
                    mostrarMensaje('El pago fue cancelado. Tu cita continúa pendiente de pago.');
                },
                onError: function () {
                    mostrarError('No se pudo completar el pago con PayPal. Intenta nuevamente.');
                },
            }).render('#paypal-button-container');
        } else {
            mostrarError('No se pudo cargar PayPal. Intenta nuevamente.');
        }
    })();
</script>


@endif
@endpush
