<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolMiddleware
{
    /**
     * Verifica que el usuario autenticado tenga el rol indicado.
     * Uso en rutas: middleware('rol:admin') / middleware('rol:cliente,abogado')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $usuario = Auth::user();

        // Primero verificar si la cuenta está activa
        if (!$usuario->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está inactiva.']);
        }

        // Luego verificar el rol
        if (!in_array($usuario->rol, $roles)) {
            // Redirigir al dashboard correcto según el rol real del usuario
            return match($usuario->rol) {
                'admin'    => redirect()->route('interno.dashboard'),
                'abogado'  => redirect()->route('abogado.dashboard'),
                'cliente'  => redirect()->route('cliente.dashboard'),
                default    => abort(403, 'No tienes permiso para acceder a esta sección.'),
            };
        }

        return $next($request);
    }
}