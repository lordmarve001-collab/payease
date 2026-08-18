<?php

return [
    'tiers' => [
        0 => [
            'label' => 'Unverified',
            'daily_limit' => 0,
            'single_txn_limit' => 0,
            'requires' => [],
            'description' => 'Phone entered, not yet OTP-verified',
        ],
        1 => [
            'label' => 'Basic',
            'daily_limit' => 5000,
            'single_txn_limit' => 2000,
            'requires' => ['phone_verified', 'pin_set'],
            'description' => 'OTP verified, PIN set — basic wallet access',
        ],
        2 => [
            'label' => 'Verified',
            'daily_limit' => 50000,
            'single_txn_limit' => 20000,
            'requires' => ['nin', 'bvn', 'next_of_kin'],
            'description' => 'NIN + BVN + Next of Kin verified — full account number',
        ],
        3 => [
            'label' => 'Premium',
            'daily_limit' => 200000,
            'single_txn_limit' => 100000,
            'requires' => ['proof_of_address'],
            'description' => 'Address verified — highest limits',
        ],
    ],
];
