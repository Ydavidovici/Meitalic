<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free‑Shipping Threshold
    |--------------------------------------------------------------------------
    */
    'free_threshold' => env('FREE_SHIPPING_THRESHOLD', 55),

    'tax_rate' => env('CART_TAX_RATE', 0.06625),

    'shipper_address' => [
        'name'        => env('SHIP_ORIGIN_NAME',     'Meitalic'),
        'phone'       => env('SHIP_ORIGIN_PHONE',    '(123) 456-7890'),
        'email'       => env('SHIP_ORIGIN_EMAIL',    'noreply@meitalic.com'),

        // these now match your .env SHIP_FROM_* keys
        'street1'     => env('SHIP_FROM_STREET1'),
        'city'        => env('SHIP_FROM_CITY'),
        'state'       => env('SHIP_FROM_STATE'),
        'postalCode'  => env('SHIP_FROM_POSTAL_CODE'),
        'country'     => env('SHIP_FROM_COUNTRY',    'US'),

        'residential' => env('SHIP_FROM_RESIDENTIAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | US States
    |--------------------------------------------------------------------------
    */
    'states' => [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ],

    /*
    |--------------------------------------------------------------------------
    | Countries (ISO 3166-1 alpha-2 codes)
    |--------------------------------------------------------------------------
    */
    'countries' => [
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
        'GB' => 'United Kingdom',
        'DE' => 'Germany',
        'FR' => 'France',
        'JP' => 'Japan',
        'CN' => 'China',
        'IN' => 'India',
        'AU' => 'Australia',
        'BR' => 'Brazil',
        'ZA' => 'South Africa',
        'NG' => 'Nigeria',
        'IL' => 'Israel'
    ],


    'shipstation' => [
        'key'      => env('SHIPSTATION_API_KEY'),
        'secret'   => env('SHIPSTATION_API_SECRET'),
        'base'     => env('SHIPSTATION_API_BASE', 'https://ssapi.shipstation.com'),
        'carriers' => explode(',', env(
            'SHIPSTATION_CARRIERS',
            ',ups,ups_walleted,fedex_walleted,'
        )),
    ],

    /*
    |--------------------------------------------------------------------------
    | UPS Credentials
    |--------------------------------------------------------------------------
    */
    'ups' => [
        'account'         => env('UPS_ACCOUNT'),

        // OAuth credentials from UPS Developer Portal
        'client_id'       => env('UPS_CLIENT_ID'),
        'client_secret'   => env('UPS_CLIENT_SECRET'),

        // URLs for token‐exchange and rate calls
        'oauth_token_url' => env('UPS_OAUTH_TOKEN_URL'),
        'rate_endpoint'   => env('UPS_RATE_ENDPOINT'),
        // Dimensional weight divisor (imperial): cubic inches ÷ 139 → pounds
        'dim_divisor' => env('UPS_DIM_DIVISOR', 139),
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
            'max_weight' => 50,
        ],
        [
            'length'     => 9.0,
            'width'      => 6.5,
            'height'     => 3.5,
            'max_weight' => 50,
        ],
    ],

];
