<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverb Server Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file is for the Reverb WebSocket server that is
    | included with Laravel. You may use this server to power your own
    | real-time features such as broadcasting, presence, and more.
    |
    */

    'apps' => [

        'provider' => 'config',

        'apps' => [
            [
                'app_id' => env('REVERB_APP_ID'),
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'options' => [
                    'host' => env('REVERB_HOST'),
                    'port' => env('REVERB_PORT', 443),
                    'scheme' => env('REVERB_SCHEME', 'https'),
                    'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                ],
                'allowed_origins' => ['*'],
                'ping_interval' => env('REVERB_APP_PING_INTERVAL', 60),
                'activity_timeout' => env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
                'max_connections' => env('REVERB_APP_MAX_CONNECTIONS'),
                'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10_000),
                'accept_client_events_from' => env('REVERB_APP_ACCEPT_CLIENT_EVENTS_FROM', 'members'),
                'rate_limiting' => [
                    'enabled' => env('REVERB_APP_RATE_LIMITING_ENABLED', false),
                    'max_attempts' => env('REVERB_APP_RATE_LIMIT_MAX_ATTEMPTS', 60),
                    'decay_seconds' => env('REVERB_APP_RATE_LIMIT_DECAY_SECONDS', 60),
                    'terminate_on_limit' => env('REVERB_APP_RATE_LIMIT_TERMINATE', false),
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    |
    | The following configuration options control the Reverb server itself.
    | You typically do not need to modify these options.
    |
    */

    'host' => env('REVERB_HOST', '127.0.0.1'),

    'port' => env('REVERB_PORT', 8080),

    'scheme' => env('REVERB_SCHEME', 'http'),

    'ssl' => [

        'cert_path' => env('REVERB_SSL_CERT_PATH'),

        'key_path' => env('REVERB_SSL_KEY_PATH'),

        'passphrase' => env('REVERB_SSL_PASSPHRASE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Here you may specify the origins that are allowed to connect to the
    | Reverb server. This is a security feature to prevent unauthorized
    | connections from other domains.
    |
    */

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('REVERB_ALLOWED_ORIGINS', env('REVERB_ALLOWED_ORIGIN', 'http://localhost:8000,http://127.0.0.1:8000')))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Reverb can persist its data to a database. This is useful for scaling
    | across multiple servers.
    |
    */

    'database' => [

        'connection' => env('REVERB_DATABASE_CONNECTION'),

        'prefix' => env('REVERB_DATABASE_PREFIX', 'reverb_'),

    ],

];