<?php

return [
    // public keeps the existing meet.jit.si integration; jaas requires all keys below.
    'provider' => env('JITSI_PROVIDER', 'public'),
    'jaas_app_id' => env('JAAS_APP_ID'),
    'jaas_key_id' => env('JAAS_KEY_ID'),
    'jaas_private_key' => env('JAAS_PRIVATE_KEY'),
];
