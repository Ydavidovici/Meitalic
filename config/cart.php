<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shopping Cart Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure settings for your shopping cart, such as
    | the sales tax rate to apply to your subtotal after discounts.
    |
    */

    /*
    |----------------------------------------------------------------------
    | Sales Tax Rate
    |----------------------------------------------------------------------
    |
    | This value is used to compute the tax on (subtotal - discount).
    | For New Jersey 6.625%, set in your .env: CART_TAX_RATE=0.06625
    |
    */
    'tax_rate' => env('CART_TAX_RATE', 0),

];
