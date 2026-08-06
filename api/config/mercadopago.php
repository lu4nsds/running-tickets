<?php

return [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
    'api_url' => env('MERCADOPAGO_API_URL', 'https://api.mercadopago.com'),
    'max_installments' => (int) env('MERCADOPAGO_MAX_INSTALLMENTS', 3),

    /*
    |--------------------------------------------------------------------------
    | OAuth Marketplace (split de pagamento)
    |--------------------------------------------------------------------------
    |
    | Credenciais da aplicação Marketplace registrada no painel de
    | desenvolvedores do Mercado Pago. Necessárias para o organizador conectar
    | a conta dele (OAuth) e para o split nativo via application_fee.
    |
    | ⚠️ Pré-requisito manual: registrar a Running Tickets como aplicação
    |    Marketplace no MP para obter app_id/client_secret e cadastrar a
    |    redirect_uri. Sem isso o OAuth e o split não funcionam.
    |
    */
    'oauth' => [
        'app_id' => env('MERCADOPAGO_APP_ID', ''),
        'client_secret' => env('MERCADOPAGO_CLIENT_SECRET', ''),
        'redirect_uri' => env('MERCADOPAGO_OAUTH_REDIRECT_URI', ''),
        'authorize_url' => env('MERCADOPAGO_OAUTH_AUTHORIZE_URL', 'https://auth.mercadopago.com.br/authorization'),
        'token_url' => env('MERCADOPAGO_OAUTH_TOKEN_URL', 'https://api.mercadopago.com/oauth/token'),
        // Janela (em segundos) antes do vencimento para renovar o token no scheduler.
        'refresh_threshold' => (int) env('MERCADOPAGO_OAUTH_REFRESH_THRESHOLD', 7 * 24 * 60 * 60),
    ],

    // Para onde o callback do OAuth redireciona o organizador de volta no admin.
    // Reaproveita a config existente do app (ADMIN_URL), não uma env própria.
    'admin_url' => env('ADMIN_URL', 'http://localhost:5174'),

    // Segredo do webhook para validar a assinatura x-signature do MP (opcional
    // enquanto não configurado — a validação é pulada se estiver vazio).
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET', ''),
];
