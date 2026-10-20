<?php

return [

    // ─── Twilio WhatsApp and SLI SMS OTP delivery ────────────
    'twilio' => [
        'account_sid'   => env('TWILIO_ACCOUNT_SID'),
        'auth_token'    => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
        'sms_from'      => env('TWILIO_SMS_FROM'),
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
