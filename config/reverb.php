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

        'default' => [
            'id' => env('REVERB_APP_ID', 1),
            'name' => env('APP_NAME', 'LexCita'),
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'enable_client_messages' => true,
            'enable_statistics' => true,
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