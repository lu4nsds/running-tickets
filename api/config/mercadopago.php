<?php

return [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    'api_url' => env('MERCADOPAGO_API_URL', 'https://api.mercadopago.com'),
    'sandbox_mode' => env('MERCADOPAGO_SANDBOX_MODE', true),
    'mock_approved' => env('MERCADOPAGO_MOCK_APPROVED', false),
];
