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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'brapi' => [
        'base_url' => env('BRAPI_BASE_URL', 'https://brapi.dev/api'),
        'token' => env('BRAPI_TOKEN'),
        'timeout' => env('BRAPI_TIMEOUT', 10),
        'retries' => env('BRAPI_RETRIES', 2),
    ],

    'hg_brasil' => [
        'base_url' => env('HG_BRASIL_BASE_URL', 'https://api.hgbrasil.com'),
        'key' => env('HG_BRASIL_KEY'),
        'timeout' => env('HG_BRASIL_TIMEOUT', 10),
        'retries' => env('HG_BRASIL_RETRIES', 2),
    ],

];
