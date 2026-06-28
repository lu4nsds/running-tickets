<?php

return [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
    'api_url' => env('MERCADOPAGO_API_URL', 'https://api.mercadopago.com'),
    'max_installments' => (int) env('MERCADOPAGO_MAX_INSTALLMENTS', 3),
];
