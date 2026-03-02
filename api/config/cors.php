<?php

# Define os domínios que podem realizar requisições à api
$domains = [];

foreach (explode(',', env('ALLOWED_ORIGINS', 'http://localhost:5173')) as $domain) {
    $domains[] = trim($domain);
}

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $domains,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
