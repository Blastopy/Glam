<?php

return [
    // 'sandbox' or 'production'
    'env' => env('MPESA_ENV', 'sandbox'),

    'base_url' => env('MPESA_ENV', 'sandbox') === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke',

    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    // Till/Paybill shortcode used for STK push
    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),

    // Must be a publicly reachable HTTPS URL (ngrok in local dev)
    'callback_url' => env('MPESA_CALLBACK_URL'),

    // What fraction of a service's price is required as deposit (e.g. 0.3 = 30%)
    'default_deposit_rate' => (float) env('MPESA_DEPOSIT_RATE', 0.3),
];
