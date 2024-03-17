<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'map' => [
        'key' => env('MAPS_KEY'),
        'alt_key' => env('MAPS_KEY_ALT')
    ],

    'firebase' => [
        'key' => env('FIREBASE_KEY')
    ],

    'delivery_rate' => env('DELIVERY_RATE'),

    'default_service_charge' => env('DEFAULT_SERVICE_CHARGE'),

    'kms_to_miles' => env('KMS_TO_MILES'),

    'voodoo' => [
        'BASE_URL' => env('VOODOO_BASE_URL'),
        'API_KEY' => env('VOODOO_API_KEY'),
    ],

    'stripe' => [
        // 'LIVE_KEY' => env('STRIPE_LIVE_KEY'),
        // 'LIVE_SECRET_KEY' => env('STRIPE_LIVE_SECRET_KEY'),
        'KEY' => env('STRIPE_KEY'),
        'SECRET_KEY' => env('STRIPE_SECRET'),
    ]
];
