<?php

return [

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
    ],

    // ─── PayPal REST (Orders API v2) ─────────────────────────
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret'    => env('PAYPAL_SECRET'),
        'mode'      => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
        'currency'  => 'USD',
        'base_uri'  => env('PAYPAL_MODE', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com',
        'timeout'   => 15,
    ],

];
