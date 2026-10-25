<?php

namespace App\Services\Agenda\Routing;

use App\Models\AgendaLocation;
use Carbon\CarbonImmutable;

interface RouteProviderInterface
{
    public function estimate(AgendaLocation $origin, AgendaLocation $destination, string $mode, CarbonImmutable $departure): ?RouteEstimate;
}
