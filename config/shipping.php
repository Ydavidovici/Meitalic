<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free‑Shipping Threshold
    |--------------------------------------------------------------------------
    |
    | Orders with a subtotal (pre‑tax, pre‑shipping) at or above this value
    | will ship for free.
    |
    */
    'free_threshold' => env('FREE_SHIPPING_THRESHOLD', 50),

    /*
    |--------------------------------------------------------------------------
    | UPS Credentials
    |--------------------------------------------------------------------------
    */
    'ups' => [
        'license' => env('UPS_LICENSE'),      // UPS Access License Number
        'user'    => env('UPS_USER'),         // UPS API Username
        'pass'    => env('UPS_PASS'),         // UPS API Password
        'account' => env('UPS_ACCOUNT'),      // UPS Shipper Number
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipper / Origin Address
    |--------------------------------------------------------------------------
    | This is your warehouse or “Ship From” address—UPS needs it to rate.
    | Fill these out in .env once you have them.
    */
    'shipper_address' => [
        'line'            => env('SHIPPING_ORIGIN_LINE', ''),      // e.g. "123 Warehouse Rd"
        'city'            => env('SHIPPING_ORIGIN_CITY', ''),      // e.g. "Raleigh"
        'state'           => env('SHIPPING_ORIGIN_STATE', ''),     // e.g. "NC"
        'postal'          => env('SHIPPING_ORIGIN_POSTAL', ''),    // e.g. "27601"
        'country'         => env('SHIPPING_ORIGIN_COUNTRY', 'US'), // ISO country code
    ],

    /*
    |--------------------------------------------------------------------------
    | Pre‑defined Box Sizes
    |--------------------------------------------------------------------------
    | We’ll pick the smallest one that fits your items.
    |
    */
    'boxes' => [
        ['length' => 12, 'width' =>  9, 'height' =>  4],
        ['length' => 16, 'width' => 12, 'height' =>  8],
        ['length' => 18, 'width' => 12, 'height' => 12],
        ['length' => 24, 'width' => 18, 'height' => 12],
        // …add more if you need…
    ],

];
