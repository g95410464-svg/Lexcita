<?php

return [
    // One deployment and one PostgreSQL database per organization. Never read
    // the organization or database name from a request, cookie or URL parameter.
    'isolated' => env('ORGANIZATION_ISOLATED', false),
    'id' => env('ORGANIZATION_ID'),
    'name' => env('ORGANIZATION_NAME'),
    'database' => env('ORGANIZATION_DATABASE'),
];
