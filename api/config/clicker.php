<?php

return [
    'economy' => [
        'starting_balance' => 1000,
        'daily_booster_max_uses' => 3,
        'daily_booster_cooldown_hours' => 4,
        'energy_restore_per_second' => 1,
        'passive' => [
            'max_hours' => 2,
            'hard_cap_multiplier' => 1.25,
        ],
        'withdraw' => [
            'min_amount' => 10000,
        ],
    ],
    'level_up' => [
        'max_energy' => 250,
        'earn_per_tap' => 1,
    ],
    'referral' => [
        'limits' => [
            'max_accounts_per_ip' => 3,
            'max_referrals_per_ip_per_referrer' => 1,
            'suspicious_accounts_per_ip' => 2,
        ],
        'base' => [
            'welcome' => 250,
            'levelUp' => [
                '1' => 0,
                '2' => 0,
                '3' => 120,
                '4' => 180,
                '5' => 0,
                '6' => 260,
                '7' => 0,
                '8' => 380,
                '9' => 0,
                '10' => 520,
            ],
        ],
        'premium' => [
            'welcome' => 500,
            'levelUp' => [
                '1' => 0,
                '2' => 0,
                '3' => 200,
                '4' => 300,
                '5' => 0,
                '6' => 450,
                '7' => 0,
                '8' => 650,
                '9' => 0,
                '10' => 900,
            ],
        ],
    ],
];
