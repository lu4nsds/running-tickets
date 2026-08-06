<?php

namespace App\Services\Payment;

use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fluxo OAuth Marketplace do Mercado Pago: gera a URL de autorização, troca o
 * authorization code por tokens e renova tokens vencidos. As credenciais da
 * aplicação Marketplace vêm de config('mercadopago.oauth').
 *
 * ⚠️ Pré-requisito manual: a Running Tickets precisa estar registrada como
 *    aplicação Marketplace no painel do Mercado Pago (app_id, client_secret,
 *    redirect_uri). Sem isso este fluxo não funciona.
 */
class MercadoPagoOAuthService
{
    /**
     * URL para onde o organizador é redirecionado para autorizar a conexão.
     */
    public function authorizationUrl(string $state): string
    {
        $config = config('mercadopago.oauth');

        $query = http_build_query([
            'client_id' => $config['app_id'],
            'response_type' => 'code',
            'platform_id' => 'mp',
            'state' => $state,
            'redirect_uri' => $config['redirect_uri'],
        ]);

        return rtrim($config['authorize_url'], '/').'?'.$query;
    }

    /**
     * Troca o authorization code recebido no callback pelos tokens do organizador.
     *
     * @return array{access_token:string,refresh_token:?string,public_key:?string,provider_account_id:?string,expires_in:?int,scopes:?string}
     */
    public function exchangeCode(string $code): array
    {
        $config = config('mercadopago.oauth');

        return $this->requestToken([
            'grant_type' => 'authorization_code',
            'client_id' => $config['app_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
        ]);
    }

    /**
     * Renova o access token de uma conta usando o refresh token.
     *
     * @return array{access_token:string,refresh_token:?string,public_key:?string,provider_account_id:?string,expires_in:?int,scopes:?string}
     */
    public function refresh(PaymentGatewayAccount $account): array
    {
        $config = config('mercadopago.oauth');

        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'client_id' => $config['app_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $account->refresh_token,
        ]);
    }

    /**
     * Persiste (cria ou atualiza) a conexão de um organizador a partir do
     * payload normalizado de tokens.
     */
    public function storeAccount(Organizer $organizer, array $tokens): PaymentGatewayAccount
    {
        return PaymentGatewayAccount::updateOrCreate(
            [
                'organizer_id' => $organizer->id,
                'gateway' => \App\Enums\PaymentGateway::MERCADOPAGO->value,
            ],
            [
                'provider_account_id' => $tokens['provider_account_id'] ?? null,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'public_key' => $tokens['public_key'] ?? null,
                'expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds((int) $tokens['expires_in'])
                    : null,
                'scopes' => $tokens['scopes'] ?? null,
                'status' => \App\Enums\PaymentAccountStatus::CONNECTED,
                'connected_at' => now(),
            ]
        );
    }

    /**
     * Chama o endpoint de token do MP e normaliza a resposta.
     */
    private function requestToken(array $payload): array
    {
        $config = config('mercadopago.oauth');

        $response = Http::asForm()->post($config['token_url'], $payload);

        if ($response->failed()) {
            Log::error('Falha na troca de token OAuth do Mercado Pago', [
                'grant_type' => $payload['grant_type'] ?? null,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível concluir a conexão com o Mercado Pago.');
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'] ?? throw new RuntimeException('Resposta OAuth do Mercado Pago sem access_token.'),
            'refresh_token' => $data['refresh_token'] ?? null,
            'public_key' => $data['public_key'] ?? null,
            'provider_account_id' => isset($data['user_id']) ? (string) $data['user_id'] : null,
            'expires_in' => $data['expires_in'] ?? null,
            'scopes' => $data['scope'] ?? null,
        ];
    }
}
