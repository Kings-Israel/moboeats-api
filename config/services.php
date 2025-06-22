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
        'alt_key' => env('MAPS_KEY_ALT'),
        'url' => env('MAPS_URL')
    ],

    'firebase' => [
        'key' => env('FIREBASE_KEY')
    ],

    'delivery_rate' => env('DELIVERY_RATE'),

    'default_service_charge' => env('DEFAULT_SERVICE_CHARGE'),

    'kms_to_miles' => env('KMS_TO_MILES', 0.621371),

    'voodoo' => [
        'BASE_URL' => env('VOODOO_BASE_URL'),
        'API_KEY' => env('VOODOO_API_KEY'),
    ],

    'bongasms' => [
        'BONGA_BASE_URL' => env('BONGA_BASE_URL', 'http://167.172.14.50:4002/v1/send-sms'),
        'BONGA_API_KEY' => env('BONGA_API_KEY'),
        'BONGA_API_SECRET' => env('BONGA_API_SECRET'),
        'BONGA_CLIENT_ID' => env('BONGA_CLIENT_ID'),
        'BONGA_SERVICE_ID' => env('BONGA_SERVICE_ID'),
    ],

    'stripe' => [
        // 'LIVE_KEY' => env('STRIPE_LIVE_KEY'),
        // 'LIVE_SECRET_KEY' => env('STRIPE_LIVE_SECRET_KEY'),
        'KEY' => env('STRIPE_KEY'),
        'SECRET_KEY' => env('STRIPE_SECRET'),
    ],

    'pochipay' => [
        'BASE_URL' => env('POCHIPAY_BASE_URL'),
        'EMAIL' => env('POCHIPAY_EMAIL'),
        'PASSWORD' => env('POCHIPAY_PASSWORD')
    ],

    'paystack' => [
        'ENV' => env('PAYSTACK_ENV'),
        'BASE_URL' => env('PAYSTACK_URL', 'https://api.paystack.co'),
        'SECRET_KEY' => env('PAYSTACK_ENV') == 'live' ? env('PAYSTACK_LIVE_SECRET_KEY') : env('PAYSTACK_TEST_SECRET_KEY'),
        'PUBLIC_KEY' => env('PAYSTACK_ENV') == 'live' ? env('PAYSTACK_LIVE_PUBLIC_KEY') : env('PAYSTACK_TEST_PUBLIC_KEY'),
    ]
];
