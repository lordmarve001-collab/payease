<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PIN Lockout Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how many failed PIN attempts are allowed before a temporary
    | lockout, and how long the lockout lasts.
    |
    */

    'pin' => [
        'max_attempts' => env('PIN_MAX_ATTEMPTS', 3),
        'lockout_duration' => (int) env('PIN_LOCKOUT_DURATION', env('APP_ENV') === 'production' ? 86400 : 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Lockout Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how many failed login attempts are allowed before a temporary
    | lockout, and how long the lockout lasts.
    |
    */

    'login' => [
        'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
        'lockout_duration' => (int) env('LOGIN_LOCKOUT_DURATION', env('APP_ENV') === 'production' ? 86400 : 60),
    ],
];
