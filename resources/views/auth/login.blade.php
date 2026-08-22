<!DOCTYPE html>
<html lang="es" class="dark">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Lex Cita - Iniciar Sesión</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700&family=Inter:wght@400;500&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "tertiary-fixed-dim": "#ddb7ff",
                        "on-primary-container": "#554300",
                        "primary": "#f2ca50",
                        "outline-variant": "#4d4635",
                        "tertiary-container": "#d09eff",
                        "background": "#131316",
                        "on-surface": "#e4e1e6",
                        "primary-fixed": "#ffe088",
                        "on-primary": "#3c2f00",
                        "surface-container-low": "#1b1b1e",
                        "on-surface-variant": "#d0c5af",
                        "surface-container-lowest": "#0e0e11",
                        "secondary": "#c0c1ff",
                        "surface-bright": "#39393c",
                        "inverse-on-surface": "#303033",
                        "surface": "#131316",
                        "secondary-container": "#3131c0",
                        "on-primary-fixed": "#241a00",
                        "on-primary-fixed-variant": "#574500",
                        "surface-dim": "#131316",
                        "tertiary-fixed": "#f0dbff",
                        "primary-container": "#d4af37",
                        "on-primary-fixed-variant": "#6900b3",
                        "on-tertiary-fixed-variant": "#6900b3",
                        "tertiary": "#e3c2ff",
                        "on-tertiary": "#490080",
                        "primary-fixed-dim": "#e9c349",
                        "inverse-primary": "#735c00",
                        "surface-container": "#1f1f22",
                        "on-error-container": "#ffdad6",
                        "on-tertiary-container": "#6700b0",
                        "secondary-fixed-dim": "#c0c1ff",
                        "on-secondary-fixed-variant": "#2f2ebe",
                        "error-container": "#93000a",
                        "error": "#ffb4ab",
                        "surface-variant": "#353438",
                        "surface-container-high": "#2a2a2d",
                        "surface-container-highest": "#353438",
                        "on-secondary-fixed": "#07006c",
                        "on-secondary-container": "#b0b2ff"
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    spacing: {
                        base: "8px",
                        "container-max": "1200px",
                        gutter: "24px",
                        "margin-mobile": "20px",
                        "margin-desktop": "64px"
                    },
                    fontFamily: {
                        "headline-xl": ["Hanken Grotesk", "sans-serif"],
                        "body-md": ["Inter", "sans-serif"],
                        "label-sm": ["JetBrains Mono", "monospace"],
                        "headline-lg-mobile": ["Hanken Grotesk", "sans-serif"],
                        "headline-lg": ["Hanken Grotesk", "sans-serif"],
                        "body-lg": ["Inter", "sans-serif"]
                    },
                    fontSize: {
                        "headline-xl": ["48px", { lineHeight: "56px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "label-sm": ["12px", { lineHeight: "16px", fontWeight: "500" }],
                        "headline-lg-mobile": ["24px", { lineHeight: "32px", fontWeight: "600" }],
                        "headline-lg": ["32px", { lineHeight: "40px", fontWeight: "600" }],
                        "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }]
                    }
                }
            }
        }
    </script>
<style>
        .glass-panel {
            background: rgba(31, 31, 34, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }

        .glow-orb-purple {
            background: radial-gradient(circle, var(--tw-colors-tertiary-fixed-dim) 0%, transparent 70%);
        }

        .glow-orb-gold {
            background: radial-gradient(circle, var(--tw-colors-primary-container) 0%, transparent 70%);
        }

        input:focus {
            outline: none;
            border-image-source: linear-gradient(to right, var(--tw-colors-tertiary-fixed-dim), var(--tw-colors-secondary));
            border-image-slice: 1;
        }
    </style>
</head>
<body class="bg-background text-on-surface font-body-md min-h-screen relative overflow-hidden flex flex-col items-center justify-center selection:bg-primary-container selection:text-on-primary-container">
<!-- Atmospheric Glows -->
<div class="glow-orb glow-orb-purple w-[600px] h-[600px] top-[-100px] right-[-100px]"></div>
<div class="glow-orb glow-orb-gold w-[500px] h-[500px] bottom-[-50px] left-[-100px]"></div>
<main class="w-full max-w-md px-margin-mobile relative z-10 flex flex-col items-center justify-center">
<!-- Brand Anchor -->
<div class="mb-10 text-center">
<h1 class="font-headline-lg text-headline-lg font-bold text-primary tracking-tight">Lex Cita</h1>

</div>
<!-- Glassmorphism Login Card -->
<div class="glass-panel w-full rounded-xl p-8 shadow-2xl relative overflow-hidden group">
<!-- Subtle hover edge light -->
<div class="absolute inset-0 border border-primary/0 group-hover:border-primary/20 rounded-xl transition-colors duration-500 pointer-events-none"></div>
<div class="text-center mb-8">
<h2 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface font-semibold">Iniciar Sesión</h2>
<p class="font-label-sm text-label-sm text-on-surface-variant mt-2">Bienvenido de nuevo.</p>
</div>
<form method="POST" action="{{ route('login') }}" class="space-y-6">
    @csrf
<!-- Email Input -->
<div class="space-y-2">
<label class="font-label-sm text-label-sm text-on-surface block" for="email">Correo Electrónico</label>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">mail</span>
<input class="w-full bg-surface-container-high border border-outline-variant text-on-surface font-body-md py-3 pl-10 pr-4 focus:ring-0 focus:border-tertiary-fixed-dim transition-all duration-300 placeholder:text-on-surface-variant/50 rounded-full" id="email" name="email" placeholder="ejemplo@bufete.com" required="" type="email">
</div>
</div>
<!-- Password Input -->
<div class="space-y-2">
<div class="flex justify-between items-center">
<label class="font-label-sm text-label-sm text-on-surface block" for="password">Contraseña</label>
<a class="font-label-sm text-label-sm text-primary hover:text-primary-fixed transition-colors" href="#">¿Olvidaste tu contraseña?</a>
</div>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">lock</span>
<input class="w-full bg-surface-container-high border border-outline-variant text-on-surface font-body-md py-3 pl-10 pr-10 focus:ring-0 focus:border-tertiary-fixed-dim transition-all duration-300 placeholder:text-on-surface-variant/50 rounded-full" id="password" name="password" placeholder="••••••••" required="" type="password">
<button class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors" type="button">
<span class="material-symbols-outlined text-lg">visibility</span>
</button>
</div>
</div>
<!-- Remember Me -->
<div class="flex items-center">
<input class="h-4 w-4 bg-surface-container-high border-outline-variant text-primary focus:ring-primary focus:ring-offset-background rounded-full" id="remember-me" name="remember" type="checkbox">
<label class="ml-2 block font-label-sm text-label-sm text-on-surface-variant" for="remember-me">
                        Mantener sesión iniciada
                    </label>
</div>
<!-- Primary Action -->
<button class="w-full bg-primary hover:bg-primary-fixed text-on-primary font-body-md font-semibold py-3 transition-all duration-300 shadow-[0_0_15px_rgba(242,202,80,0.2)] hover:shadow-[0_0_20px_rgba(242,202,80,0.4)] flex justify-center items-center gap-2 rounded-full" type="submit">
<span class="">Ingresar</span>
<span class="material-symbols-outlined text-sm">arrow_forward</span>
</button>
</form>
<div class="mt-8 relative">
<div class="absolute inset-0 flex items-center">
<div class="w-full border-t border-outline-variant"></div>
</div>
<div class="relative flex justify-center text-sm">
<span class="px-2 bg-transparent text-on-surface-variant font-label-sm text-label-sm" style="background-color: #1a1a1c;">O</span>
</div>
</div>
<div class="mt-8">
<!-- Secondary Action (Google) -->
<a href="{{ route('google.redirect') }}" class="w-full bg-transparent border border-outline-variant hover:border-on-surface text-on-surface font-body-md py-3 transition-all duration-300 flex justify-center items-center gap-3 rounded-full">
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43 .35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22 .81-.62z" fill="#FBBC05"></path>
<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.304 4.53 6.16-4.53z" fill="#EA4335"></path>
</svg>
<span>Continuar con Google</span>
</a>
</div>
<div class="mt-8 text-center">
<p class="font-label-sm text-label-sm text-on-surface-variant">
                    ¿Sin cuenta? <a class="text-primary hover:text-primary-fixed transition-colors font-semibold" href="#">Crear cuenta gratuita</a>
</p>
</div>
</div>
<!-- Footer Links outside card for cleaner look -->
<div class="mt-8 flex gap-4 font-label-sm text-label-sm text-on-surface-variant/70">
<a class="hover:text-on-surface transition-colors" href="#">Términos y Condiciones</a>
<span class="">•</span>
<a class="hover:text-on-surface transition-colors" href="#">Soporte</a>
</div>
</main>
<!-- Footer Component Execution -->
<footer class="flex flex-col md:flex-row justify-between items-center px-margin-desktop py-8 w-full absolute bottom-0 z-40 bg-transparent">
<div class="text-on-surface font-bold font-body-md text-body-md mb-4 md:mb-0">© 2026 Lex Cita. All rights reserved.</div>
<nav class="flex gap-6">
<a class="text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100 font-label-sm text-label-sm" href="#">Privacy Policy</a>
<a class="text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100 font-label-sm text-label-sm" href="#">Terms of Service</a>
<a class="text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100 font-label-sm text-label-sm" href="#">Legal Advice</a>
<a class="text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100 font-label-sm text-label-sm" href="#">Support</a>
</nav>
</footer>


</body></html>