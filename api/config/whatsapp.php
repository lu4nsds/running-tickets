<?php

return [
    'gateway' => [
        'enabled'         => env('WHATSAPP_GATEWAY_ENABLED', false),
        'base_url'        => env('WHATSAPP_GATEWAY_BASE_URL', 'http://whatsapp-gateway:3000'),
        'api_key'         => env('WHATSAPP_GATEWAY_API_KEY'),
        'tenant_uuid'     => env('WHATSAPP_GATEWAY_TENANT_UUID'),
        'timeout_seconds' => env('WHATSAPP_GATEWAY_TIMEOUT', 15),
    ],
];
