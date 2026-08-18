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

    'mmo' => [
        'driver' => env('MMO_DRIVER', 'mock'),
    ],

    'mock_mmo' => [
        'latency_min_ms' => (int) env('MOCK_MMO_LATENCY_MIN_MS', 300),
        'latency_max_ms' => (int) env('MOCK_MMO_LATENCY_MAX_MS', 600),
        'failure_rate' => (float) env('MOCK_MMO_FAILURE_RATE', 0),
        'force_fail' => env('MOCK_MMO_FORCE_FAIL'),
    ],

    'platform_liquidity' => [
        'master_balance' => (float) env('PLATFORM_MASTER_BALANCE', 0),
        'minimum_threshold' => (float) env('PLATFORM_LIQUIDITY_MINIMUM_THRESHOLD', 50000),
    ],

    'vtpass' => [
        'driver' => env('VTPASS_DRIVER', 'mock'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'termii'),
    ],

    'termii' => [
        'api_key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'PayEase'),
        'base_url' => env('TERMII_BASE_URL', 'https://v3.api.termii.com'),
        'force_ip' => env('TERMII_FORCE_IP'),
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'sender_id' => env('AFRICASTALKING_SENDER_ID', 'PayEase'),
        'environment' => env('AFRICASTALKING_ENVIRONMENT', 'sandbox'),
    ],

];
