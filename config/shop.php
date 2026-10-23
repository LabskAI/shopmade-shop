<?php

return [
    /*
     * Stripe Checkout. Leave the keys empty to take orders without online payment (staff mark them paid).
     */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],
];
