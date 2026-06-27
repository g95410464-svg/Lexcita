@extends('layouts.app')
@section('title', 'Pagar Cita')

@section('content')
<div class="max-w-lg mx-auto">

    <div class="mb-8">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-secondary mb-2">Paso final</p>
        <h1 class="font-caslon text-4xl font-normal text-on-surface">Confirmar Pago</h1>
        <p class="text-outline mt-1 text-sm">Revisa el resumen y completa el pago con PayPal.</p>
    </div>

    <div class="bg-surface-container border border-outline-variant p-6 mb-6">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-4">Resumen de tu cita</p>
        <div class="flex items-start gap-4 mb-5 pb-5 border-b border-outline-variant">
            <div class="bg-secondary text-on-secondary text-center px-3 py-2 flex-shrink-0">
                <p class="text-[10px] font-grotesk font-bold tracking-widest uppercase">{{ $cita->fecha->format('M') }}</p>
                <p class="font-caslon text-2xl font-bold leading-none">{{ $cita->fecha->format('d') }}</p>
            </div>
            <div>
                <p class="font-grotesk font-bold text-on-surface uppercase tracking-wider text-sm">
                    {{ str_replace('_', ' ', $cita->tipo) }}
                </p>
                <p class="text-outline text-sm mt-1">
                    <span class="material-symbols-outlined text-[14px]">person</span>
                    {{ $cita->abogado->nombre }}
                </p>
                <p class="text-outline text-sm">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    {{ $cita->hora_inicio }} · {{ ucfirst($cita->modalidad) }}
                </p>
            </div>
        </div>
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm text-outline">Código de reserva</span>
            <span class="font-mono text-secondary font-bold text-sm">{{ $cita->codigo }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm text-outline">Total</span>
            <span class="font-caslon text-3xl text-on-surface">${{ number_format($cita->monto, 2) }} <span class="text-sm text-outline">USD</span></span>
        </div>
    </div>

    <div class="bg-surface-container border border-outline-variant p-6 mb-4">
        <p class="text-[11px] font-grotesk font-semibold tracking-[.18em] uppercase text-outline mb-5">Pagar con PayPal</p>

        <script src="https://www.paypal.com/sdk/js?client-id=BAAMOE8n4X54Yqctc1fMtLG9b8Gs65vbA3kRTk_yfIc7Ybf2OZztPdXO3jmlONSiPSKXi73QbVzos4LPHI&components=hosted-buttons&disable-funding=venmo&currency=USD"></script>

        <div id="paypal-container-HHFA4DKJHYE5Q"></div>

        <script>
            paypal.HostedButtons({
                hostedButtonId: "HHFA4DKJHYE5Q",
                onApprove: function(data) {
                    window.location.href = "{{ route('pago.exito') }}";
                },
                onCancel: function() {
                    window.location.href = "{{ route('pago.cancelado') }}";
                },
                onError: function(err) {
                    alert('Ocurrió un error con PayPal. Intenta de nuevo.');
                }
            }).render("#paypal-container-HHFA4DKJHYE5Q");
        </script>
    </div>

    <p class="text-center text-xs text-outline">
        Al completar el pago tu cita quedará confirmada automáticamente y recibirás un mensaje de WhatsApp.
    </p>

</div>
@endsection