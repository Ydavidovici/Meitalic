<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free‑Shipping Threshold
    |--------------------------------------------------------------------------
    */
    'free_threshold' => env('FREE_SHIPPING_THRESHOLD', 50),

    /*
    |--------------------------------------------------------------------------
    | UPS Credentials
    |--------------------------------------------------------------------------
    */
    'ups' => [
        'license' => env('UPS_LICENSE'),
        'user'    => env('UPS_USER'),
        'pass'    => env('UPS_PASS'),
        'account' => env('UPS_ACCOUNT'),
        // Dimensional weight divisor (imperial): cubic inches ÷ 139 → pounds
        'dim_divisor' => env('UPS_DIM_DIVISOR', 139),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipper / Origin Address
    |--------------------------------------------------------------------------
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
    | Envelope Size
    |--------------------------------------------------------------------------
    | Client‑provided:
    |  • Internal Length (longest side): 7″
    |  • Internal Width:                4″
    |  • Maximum Thickness (gusset):   1″
    |  • Max Weight Capacity:          1 lb
    |--------------------------------------------------------------------------
    */
    'envelope' => [
        'length'      => 7.0,   // L_env
        'width'       => 4.0,   // W_env
        'height'      => 1.0,   // H_env (gusset thickness)
        'max_weight'  => 1.0,   // lbs
    ],

    /*
    |--------------------------------------------------------------------------
    | Pre‑defined Box Sizes
    |--------------------------------------------------------------------------
    | We pick the smallest container that fits.
    |
    | Volumes:
    |  • small box:  8.5 × 3.5 × 3.5  = 104 cu in   (Volume of rectangular prism)
    |  • big box:    9.0 × 6.5 × 3.5  = 204 cu in
    |--------------------------------------------------------------------------
    */
    'boxes' => [
        [
            'length'     => 8.5,
            'width'      => 3.5,
            'height'     => 3.5,
            'max_weight' => 50,     // e.g. UPS small‑box weight limit
        ],
        [
            'length'     => 9.0,
            'width'      => 6.5,
            'height'     => 3.5,
            'max_weight' => 50,     // e.g. UPS large‑box weight limit
        ],
    ],

];
