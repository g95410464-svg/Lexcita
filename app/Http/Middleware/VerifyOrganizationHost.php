<?php

namespace App\Http\Middleware;

use App\Support\OrganizationDeployment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOrganizationHost
{
    public function __construct(private OrganizationDeployment $organization) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Railway health checks may use an internal host. This path has no
        // session or organization data; all other routes require the exact host.
        if ($this->organization->enabled()
            && !($request->isMethod('GET') && $request->getPathInfo() === '/up')
            && strtolower($request->getHost()) !== $this->organization->host()) {
            return new Response('Dirección no habilitada para esta organización.', 421, ['Cache-Control' => 'no-store']);
        }

        return $next($request);
    }
}
