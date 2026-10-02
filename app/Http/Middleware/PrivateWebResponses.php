<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivateWebResponses
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        // Laravel pages contain session/CSRF data. Only public static files may be cached by a CDN.
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
