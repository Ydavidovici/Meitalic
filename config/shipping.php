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
    | Fill these out in your .env (see below).
    */
    'shipper_address' => [
        'line'    => env('SHIPPING_ORIGIN_LINE',   '131 Spook Rock Rd'),
        'city'    => env('SHIPPING_ORIGIN_CITY',   'Suffern'),
        'state'   => env('SHIPPING_ORIGIN_STATE',  'NY'),
        'postal'  => env('SHIPPING_ORIGIN_POSTAL', '10901'),
        'country' => env('SHIPPING_ORIGIN_COUNTRY','US'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pre‑defined Box Sizes
    |--------------------------------------------------------------------------
    | We’ll pick the smallest one that fits your items.
    |
    | Client‑provided specs:
    |  • small box:  8½″ × 3½″ × 3½″  (104 cu in)
    |  • big box:    9″ × 6½″ × 3½″   (204 cu in)
    */
    'boxes' => [
        [
            'length' => 8.5,
            'width'  => 3.5,
            'height' => 3.5,
        ],
        [
            'length' => 9.0,
            'width'  => 6.5,
            'height' => 3.5,
        ],
    ],

];
