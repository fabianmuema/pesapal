<?php

return [
    'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
    'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
    'currency' => env('PESAPAL_CURRENCY'),
    'callback_url' => env('PESAPAL_CALLBACK_URL'),
    'live' => env('PESAPAL_LIVE', false),
];
