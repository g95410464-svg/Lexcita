<?php

return [

    // ─── Twilio WhatsApp OTP delivery ───────────────────────
    'twilio' => [
        'account_sid'   => env('TWILIO_ACCOUNT_SID'),
        'auth_token'    => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'whatsapp_content_sid' => env('TWILIO_WHATSAPP_CONTENT_SID'),
    ],

    // ─── Twilio SendGrid Email API ───────────────────────────
    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'from_email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME', 'Servicios Legales Integrados'),
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
