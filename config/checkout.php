<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Minimum checkout / top-up amount (DZD)
    |--------------------------------------------------------------------------
    |
    | Orders below this server-calculated charge are blocked. Users must top up
    | their wallet first. Same value applies as the minimum deposit amount.
    | Set to 0 to disable the guard (useful for local development).
    |
    */
    'minimum_amount_dzd' => max(0, (int) env('CHECKOUT_MINIMUM_AMOUNT_DZD', 500)),
];
