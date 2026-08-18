<?php

return [

    // ─── Twilio WhatsApp ──────────────────────────────────────
    'twilio' => [
        'account_sid'   => env('TWILIO_ACCOUNT_SID'),
        'auth_token'    => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
    ],

];
