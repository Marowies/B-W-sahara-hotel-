<?php

return [
    // Opt in only after assigning both hosts to the same environment.
    'enabled' => env('CANONICAL_HOST_ENABLED', false),
    'origin' => env('APP_URL'),
    'status' => (int) env('CANONICAL_HOST_STATUS', 302),
    'permanent_confirmed' => env('CANONICAL_HOST_PERMANENT_CONFIRMED', false),
    'production_host' => 'bwsaharaskyhotel.com',
    'excluded_paths' => [
        'booking', 'bookings', 'checkout', 'payment', 'payments', 'paypal', 'stripe',
        'webhook', 'webhooks', 'callback', 'callbacks', 'customer', 'customers',
        'login', 'register', 'logout', 'password', 'admin', 'api', 'auth',
    ],
    'sensitive_query_keys' => [
        'signature', 'token', '_token', 'checkout_token', 'transaction_id',
        'payment_intent', 'payment_intent_client_secret', 'payerid', 'paymentid',
        'code', 'order_id',
    ],
];
