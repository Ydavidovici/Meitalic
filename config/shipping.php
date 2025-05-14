<?php
return [
    'free_threshold' => env('FREE_SHIPPING_THRESHOLD', 50),
    'ups' => [
        'license' => env('UPS_LICENSE'),
        'user'    => env('UPS_USER'),
        'pass'    => env('UPS_PASS'),
        'account' => env('UPS_ACCOUNT'),
    ],
];
