<?php

namespace App\Services\Agenda;

use App\Models\AgendaLocation;
use App\Services\Agenda\Routing\{ManualRouteProvider, OpenRouteServiceProvider, RouteEstimate};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class TravelTimeService
{
    public function estimate(?AgendaLocation $from, ?AgendaLocation $to, string $mode, CarbonImmutable $departure): ?RouteEstimate
    {
        if (!$from || !$to || !$from->active || !$to->active || $from->abogado_id !== $to->abogado_id) {
            return null;
        }
        if ($from->id === $to->id || ($from->latitude === $to->latitude && $from->longitude === $to->longitude)) {
            return new RouteEstimate(0, 0, 'mismo_lugar', 'sin_desplazamiento');
        }
        $manual = app(ManualRouteProvider::class)->estimate($from, $to, $mode, $departure);
        if ($manual || config('agenda.routing_provider') !== 'openrouteservice') {
            return $manual;
        }
        $key = 'agenda:route:'.hash('sha256', json_encode([
            $from->abogado_id, $from->latitude, $from->longitude, $to->latitude, $to->longitude, $mode,
        ], JSON_THROW_ON_ERROR));
        // These estimates have no traffic prediction, so reuse the directed route
        // across departure times. Coordinates/mode changes yield another key.
        $value = Cache::get($key);
        if (is_array($value)) {
            return new RouteEstimate(...$value);
        }
        $estimate = app(OpenRouteServiceProvider::class)->estimate($from, $to, $mode, $departure);
        if ($estimate) {
            Cache::put($key, [$estimate->seconds, $estimate->meters, $estimate->provider, $estimate->quality],
                now()->addMinutes(config('agenda.routing_cache_minutes')));
        }
        return $estimate;
    }
}
