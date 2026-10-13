<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1d2a44">
<title>@yield('title', 'Portal') · Servicios Legales Integrados</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('images/sli-mark.svg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/sli.css', 'resources/js/sli.js'])
@stack('styles')
