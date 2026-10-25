<?php

namespace App\Services\Agenda\Routing;

use App\Models\AgendaLocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class OpenRouteServiceProvider implements RouteProviderInterface
{
    private int $requests = 0;
    public const MODES = ['driving-car', 'foot-walking', 'cycling-regular'];

    public function estimate(AgendaLocation $origin, AgendaLocation $destination, string $mode, CarbonImmutable $departure): ?RouteEstimate
    {
        if (!in_array($mode, self::MODES, true) || !config('agenda.routing_api_key')
            || $this->requests >= config('agenda.routing_max_per_request')
            || Cache::has('agenda:routing:cooldown')) {
            return null;
        }
        $budget = 'agenda:routing:requests';
        if (RateLimiter::tooManyAttempts($budget, config('agenda.routing_requests_per_minute'))) {
            return null;
        }
        RateLimiter::hit($budget, 60);
        $this->requests++;
        try {
            $response = Http::acceptJson()->withHeaders(['Authorization' => config('agenda.routing_api_key')])
                ->withOptions(['allow_redirects' => false])->connectTimeout(2)
                ->timeout(config('agenda.routing_timeout'))
                ->post('https://api.openrouteservice.org/v2/directions/'.$mode.'/json', [
                    'coordinates' => [[$origin->longitude, $origin->latitude], [$destination->longitude, $destination->latitude]],
                    'instructions' => false, 'geometry' => false,
                ]);
            $seconds = $response->json('routes.0.summary.duration');
            $meters = $response->json('routes.0.summary.distance');
            if (!$response->successful() || !is_numeric($seconds) || !is_numeric($meters)
                || $seconds < 0 || $seconds > 86400 || $meters < 0 || $meters > 6000000) {
                Cache::put('agenda:routing:cooldown', true, 60);
                return null;
            }
            // No departure-time/traffic claim: this endpoint provides a route estimate.
            return new RouteEstimate((int) ceil($seconds), (int) ceil($meters), 'openrouteservice', 'sin_trafico');
        } catch (\Throwable) {
            Cache::put('agenda:routing:cooldown', true, 60);
            return null; // No request exception, key or private coordinates in logs/UI.
        }
    }
}
