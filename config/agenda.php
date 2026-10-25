<?php

return [
    'enabled' => env('AGENDA_SMART_ENABLED', true),
    'timezone' => 'America/El_Salvador',
    'routing_provider' => env('ROUTING_PROVIDER', 'manual'),
    'routing_api_key' => env('ROUTING_API_KEY'),
    'routing_timeout' => max(1, min(8, (int) env('ROUTING_TIMEOUT_SECONDS', 4))),
    'routing_requests_per_minute' => max(1, min(40, (int) env('ROUTING_REQUESTS_PER_MINUTE', 20))),
    'routing_cache_minutes' => 360,
    'routing_max_per_request' => 4,
    'margin_minutes' => 15,
    'proposal_hours' => 24,
    'urgent_conflict_minutes' => 120,
];
