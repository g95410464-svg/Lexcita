<?php

return [
    // Railway overwrites X-Real-IP at its HTTP ingress. Enable only behind that ingress.
    'railway_ingress' => env('TRUST_RAILWAY_INGRESS', (bool) env('RAILWAY_ENVIRONMENT_ID')),

    // Activate only after the Cloudflare proxy injects this secret into origin requests.
    'origin_secret' => env('CLOUDFLARE_ORIGIN_SECRET'),

    'limits' => [
        'login_identity' => 5,
        'login_ip' => 60,
        'register_minute' => 10,
        'register_hour' => 30,
        'oauth' => 20,
        'portal' => 120,
        'slots' => 60,
        'booking' => 5,
        'payments' => 10,
        'writes' => 30,
        'video' => 240,
        'broadcast' => 60,
    ],
];
