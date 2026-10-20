@extends('layouts.auth')
@section('title', 'Verifica tu correo')
@section('content')
<a class="otp-back" href="{{ route('registro') }}"><span aria-hidden="true">←</span> Volver al registro</a>
<section class="otp-card" aria-labelledby="otp-title">
    <div class="otp-icon" aria-hidden="true"><span class="material-symbols-outlined">mark_email_unread</span></div>
    <div class="otp-heading">
        <span class="sli-eyebrow">UN PASO MÁS PARA CONTINUAR</span>
        <h2 id="otp-title">Verifica tu correo</h2>
        <p>Enviamos un código de seis dígitos a</p>
        <strong>{{ $contacts['email'] }}</strong>
    </div>

    <form id="otp-form" action="{{ route('registro.verificar.post') }}" method="POST" data-submit-once>
        @csrf
        <input type="hidden" id="email_code" name="email_code">
        <fieldset class="otp-code-fieldset">
            <legend>Ingresa el código de verificación</legend>
            <div class="otp-digits" role="group" aria-label="Código de verificación de seis dígitos">
                @for ($digit = 1; $digit <= 6; $digit++)
                    <input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1"
                        aria-label="Dígito {{ $digit }}" autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}"
                        required>
                @endfor
            </div>
        </fieldset>

        <div class="otp-expiry" role="status" aria-live="polite">
            <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
            <span id="otp-expiry-label">El código vence en</span>
            <strong id="otp-countdown" data-expires-at="{{ $contacts['expires_at'] }}">10:00</strong>
        </div>

        <button id="otp-verify" class="sli-button sli-button-primary sli-button-wide otp-verify" type="submit" data-label="Verificar cuenta">Verificar cuenta</button>
        <p class="otp-attempt-note">Tienes hasta 5 intentos para confirmar el código.</p>
    </form>

    <div class="otp-resend">
        <p>¿No recibiste el código?</p>
        <form action="{{ route('registro.verificar.resend') }}" method="POST" data-submit-once>
            @csrf
            <button id="otp-resend" class="otp-resend-button" type="submit" data-resend-at="{{ $contacts['resend_at'] }}" data-label="Reenviar código">
                <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
                <span id="otp-resend-label">Reenviar en 60 s</span>
            </button>
        </form>
    </div>
</section>
<p class="sli-auth-switch">¿Necesitas corregir tus datos? <a href="{{ route('registro') }}">Volver al registro</a></p>
@endsection

@push('styles')
<style>
    .otp-back { display:inline-flex; align-items:center; gap:9px; margin-bottom:20px; color:#63778a; font-size:12px; text-decoration:none; }
    .otp-back:hover { color:var(--navy); }
    .otp-card { padding:28px; border:1px solid #e2e6e8; border-top:3px solid #b99a62; border-radius:8px; background:linear-gradient(145deg,#fff 0%,#fcfbf8 100%); box-shadow:0 16px 42px #162a3a0e; }
    .otp-icon { display:grid; place-items:center; width:48px; height:48px; margin:0 auto 20px; border:1px solid #e7dec9; border-radius:50%; background:#f8f4eb; color:#9b7b42; }
    .otp-icon .material-symbols-outlined { font-size:23px; }
    .otp-heading { text-align:center; }
    .otp-heading .sli-eyebrow { color:#9b7b42; }
    .otp-heading h2 { margin-top:9px; }
    .otp-heading p { margin:11px 0 2px; color:var(--muted); font-size:13px; line-height:1.6; }
    .otp-heading strong { color:var(--navy); font-size:13px; overflow-wrap:anywhere; }
    .otp-code-fieldset { min-width:0; margin:25px 0 18px; padding:0; border:0; }
    .otp-code-fieldset legend { width:100%; margin-bottom:11px; color:var(--ink); font-size:12px; font-weight:600; text-align:center; }
    .otp-digits { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:8px; }
    .otp-digit { width:100%; min-width:0; height:53px; padding:0; border:1px solid #d9e1e8; border-radius:6px; background:#fff; color:var(--navy); font-size:21px; font-weight:700; text-align:center; outline:0; transition:border-color .15s,box-shadow .15s,background .15s; }
    .otp-digit:first-child { background:#f8f6f1; border-color:#d7c9ab; }
    .otp-digit:focus { border-color:#a98a51; box-shadow:0 0 0 3px #b99a6224; }
    .otp-expiry { display:flex; align-items:center; gap:8px; margin-bottom:18px; padding:12px 14px; border:1px solid #e5e8ea; border-radius:6px; background:#f7f8f8; color:#596d7d; font-size:12px; }
    .otp-expiry .material-symbols-outlined { color:#9b7b42; font-size:17px; }
    .otp-expiry strong { margin-left:auto; color:var(--navy); font-variant-numeric:tabular-nums; }
    .otp-expiry.is-expired { border-color:#efd4d7; background:#fff3f3; color:#9b3440; }
    .otp-expiry.is-expired .material-symbols-outlined,.otp-expiry.is-expired strong { color:#9b3440; }
    .otp-verify { min-height:48px!important; }
    .otp-verify:disabled { cursor:not-allowed; opacity:.55; }
    .otp-attempt-note { margin:10px 0 0; color:#83909b; font-size:10px; text-align:center; }
    .otp-resend { margin-top:23px; padding-top:19px; border-top:1px solid #e8edf1; text-align:center; }
    .otp-resend p { margin-bottom:7px; color:var(--muted); font-size:12px; }
    .otp-resend-button { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:34px; color:#8b744a; font-size:12px; font-weight:700; }
    .otp-resend-button:disabled { cursor:wait; color:#9aa4ad; }
    .otp-resend-button .material-symbols-outlined { font-size:16px; }
    @media(max-width:430px) { .otp-card { padding:23px 17px; }.otp-digits { gap:6px; }.otp-digit { height:49px; font-size:19px; } }
    @media(prefers-reduced-motion:reduce) { .otp-digit { transition:none; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const form = document.getElementById('otp-form');
    if (!form) return;

    const digits = [...form.querySelectorAll('.otp-digit')];
    const combined = document.getElementById('email_code');
    const verifyButton = document.getElementById('otp-verify');
    const countdown = document.getElementById('otp-countdown');
    const expiryBox = document.querySelector('.otp-expiry');
    const expiryLabel = document.getElementById('otp-expiry-label');
    const resendButton = document.getElementById('otp-resend');
    const resendLabel = document.getElementById('otp-resend-label');
    const expiresAt = Number(countdown.dataset.expiresAt) * 1000;
    const resendAt = Number(resendButton.dataset.resendAt) * 1000;

    const fillDigits = (value, start = 0) => {
        const numbers = value.replace(/\D/g, '').slice(0, digits.length - start);
        [...numbers].forEach((number, offset) => { digits[start + offset].value = number; });
        const next = Math.min(start + numbers.length, digits.length - 1);
        digits[next].focus();
    };

    digits.forEach((input, index) => {
        input.addEventListener('input', () => {
            const value = input.value.replace(/\D/g, '');
            input.value = value.slice(-1);
            if (value.length > 1) fillDigits(value, index);
            else if (input.value && index < digits.length - 1) digits[index + 1].focus();
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && index > 0) digits[index - 1].focus();
            if (event.key === 'ArrowLeft' && index > 0) digits[index - 1].focus();
            if (event.key === 'ArrowRight' && index < digits.length - 1) digits[index + 1].focus();
        });
        input.addEventListener('paste', (event) => {
            event.preventDefault();
            fillDigits(event.clipboardData.getData('text'), index);
        });
    });

    form.addEventListener('submit', (event) => {
        combined.value = digits.map((input) => input.value).join('');
        if (combined.value.length !== digits.length) {
            event.preventDefault();
            digits.find((input) => !input.value)?.focus();
        }
    });

    const updateTimers = () => {
        const now = Date.now();
        const remaining = Math.max(0, Math.ceil((expiresAt - now) / 1000));
        const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
        const seconds = String(remaining % 60).padStart(2, '0');
        countdown.textContent = `${minutes}:${seconds}`;

        if (remaining === 0) {
            expiryBox.classList.add('is-expired');
            expiryLabel.textContent = 'El código venció';
            verifyButton.disabled = true;
            verifyButton.textContent = 'Código vencido';
            resendButton.disabled = true;
            resendLabel.textContent = 'Vuelve al registro para solicitar otro';
            return;
        }

        const resendRemaining = Math.max(0, Math.ceil((resendAt - now) / 1000));
        resendButton.disabled = resendRemaining > 0;
        resendLabel.textContent = resendRemaining > 0 ? `Reenviar en ${resendRemaining} s` : 'Reenviar código';
    };

    updateTimers();
    window.setInterval(updateTimers, 1000);
    digits[0].focus({ preventScroll: true });
})();
</script>
@endpush
