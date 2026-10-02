<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyOrigin
{
    public function handle(Request $request, Closure $next)
    {
        $secret = config('traffic.origin_secret');

        // Railway health checks do not pass through Cloudflare.
        if ($secret && !($request->is('up') && $request->isMethod('GET'))) {
            $provided = $request->header('X-Lexcita-Origin', '');
            if (!is_string($provided) || !hash_equals($secret, $provided)) {
                return response('Acceso no permitido.', 403, ['Cache-Control' => 'private, no-store']);
            }
        }

        return $next($request);
    }
}
