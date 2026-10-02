<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espera un momento | Lexcita</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #131316; color: #e4e1e6; font: 1rem/1.6 system-ui, sans-serif; }
        main { max-width: 32rem; padding: 2rem; }
        h1, a { color: #f2ca50; }
    </style>
</head>
<body>
    <main>
        <h1>Espera un momento</h1>
        <p role="alert">{{ $message }}</p>
        <p>Cuando termine la espera, vuelve a la página anterior e inténtalo otra vez.</p>
        <a href="{{ route('login') }}">Ir al inicio de sesión</a>
    </main>
</body>
</html>
