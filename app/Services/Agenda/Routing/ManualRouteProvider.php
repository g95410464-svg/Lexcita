<?php

namespace App\Services\Agenda\Routing;

use App\Models\AgendaLocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ManualRouteProvider implements RouteProviderInterface
{
    public function estimate(AgendaLocation $origin, AgendaLocation $destination, string $mode, CarbonImmutable $departure): ?RouteEstimate
    {
        $minutes = DB::table('agenda_manual_routes')->where('abogado_id', $origin->abogado_id)
            ->where('origin_id', $origin->id)->where('destination_id', $destination->id)
            ->where('mode', $mode)->value('minutes');
        return $minutes === null ? null : new RouteEstimate((int) $minutes * 60, null, 'manual', 'manual');
    }
}
