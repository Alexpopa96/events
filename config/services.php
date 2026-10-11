<?php

// Tracked event days => [from hour, to hour] (local time) the forecast should focus on.
$weatherEvents = [
    '2026-11-12' => [13, 14],
    '2026-11-14' => [11, 16],
    '2026-11-15' => [12, 18],
];

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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anaf' => [
        'tva_url' => env('ANAF_TVA_URL', 'https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva'),
    ],

    'accuweather' => [
        'key' => env('ACCUWEATHER_API_KEY'),
        'base_url' => env('ACCUWEATHER_BASE_URL', 'https://dataservice.accuweather.com'),
        // 287430 = Bucharest; look up others via /locations/v1/cities/search
        'location_key' => env('ACCUWEATHER_LOCATION_KEY', '287430'),
        'location_name' => env('ACCUWEATHER_LOCATION_NAME', 'București'),
        'dates' => array_keys($weatherEvents),
        'event_windows' => $weatherEvents,
    ],

    'open_meteo' => [
        'ensemble_url' => env('OPEN_METEO_ENSEMBLE_URL', 'https://ensemble-api.open-meteo.com/v1/ensemble'),
        'seasonal_url' => env('OPEN_METEO_SEASONAL_URL', 'https://seasonal-api.open-meteo.com/v1/seasonal'),
        // Boldești-Scăieni (same spot as ACCUWEATHER_LOCATION_KEY=275387)
        'latitude' => env('OPEN_METEO_LATITUDE', 45.016),
        'longitude' => env('OPEN_METEO_LONGITUDE', 26.027),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
