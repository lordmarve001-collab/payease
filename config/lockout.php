<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PIN Lockout Configuration
    |--------------------------------------------------------------------------
    |
    | Controls brute-force protection for PIN verification across the platform.
    | After max_attempts failures, the user is locked out for lockout_duration seconds.
    |
    */

    'pin' => [

        'max_attempts' => (int) env('PIN_MAX_ATTEMPTS', 5),

        'lockout_duration' => (int) env('PIN_LOCKOUT_DURATION', 900), // 15 minutes

    ],

];
