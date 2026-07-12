<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GC Tu Conexión Legal — Verifica tu correo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-primary flex items-center justify-center">

    <div class="w-full max-w-md mx-auto px-6">
        <div class="text-center mb-8">
            <p class="font-caslon text-2xl font-bold text-secondary tracking-wide">GC</p>
            <p class="text-outline text-xs mt-0.5">GC Tu Conexión Legal</p>
        </div>

        <div class="bg-surface border border-border rounded-2xl p-8 text-center">

            {{-- Ícono de correo --}}
            <div class="w-16 h-16 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h1 class="text-xl font-semibold text-white mb-3">Revisa tu correo</h1>
            <p class="text-text-muted text-sm leading-relaxed mb-6">
                Te enviamos un enlace de verificación a tu dirección de correo electrónico.
                Haz clic en el enlace para activar tu cuenta y poder iniciar sesión.
            </p>

            <p class="text-text-muted text-xs mb-6">
                Si no ves el correo, revisa tu carpeta de spam o correo no deseado.
            </p>

            {{-- Reenviar correo --}}
            @if (session('reenviado'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-lg px-4 py-3 mb-4">
                    ✓ Correo reenviado correctamente.
                </div>
            @endif

            <form method="POST" action="{{ route('verificacion.reenviar') }}">
                @csrf
                <button type="submit"
                        class="w-full bg-secondary text-primary font-semibold py-2.5 rounded-xl hover:bg-secondary/90 transition-colors text-sm">
                    Reenviar correo de verificación
                </button>
            </form>

            <a href="{{ route('login') }}"
               class="block mt-4 text-xs text-text-muted hover:text-secondary transition-colors">
                Volver al inicio de sesión
            </a>
        </div>

        <p class="text-center text-text-muted text-xs mt-8">
            © {{ date('Y') }} GC Tu Conexión Legal.
        </p>
    </div>

</body>
</html>