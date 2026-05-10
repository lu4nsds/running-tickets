<?php

return [
    'enabled'   => env('WHATSAPP_GATEWAY_ENABLED', false),
    'base_url'  => env('WHATSAPP_GATEWAY_BASE_URL', 'http://whatsapp-gateway:3000'),
    'api_key'   => env('WHATSAPP_GATEWAY_API_KEY', ''),
    'timeout'   => (int) env('WHATSAPP_GATEWAY_TIMEOUT', 15),
    'tenant_id' => env('WHATSAPP_PLATFORM_TENANT_ID', 'running-tickets-platform'),
];
