<?php

return [
    'platform_access_token' => env('MERCADOPAGO_PLATFORM_ACCESS_TOKEN'),
    'platform_public_key' => env('MERCADOPAGO_PLATFORM_PUBLIC_KEY'),
    'api_url' => env('MERCADOPAGO_API_URL', 'https://api.mercadopago.com'),
    'sandbox_mode' => env('MERCADOPAGO_SANDBOX_MODE', true),
];
