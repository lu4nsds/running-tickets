<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Http;

class MercadoPagoService
{
    /**
     * Valida credenciais do Mercado Pago via API
     */
    public function validateCredentials(string $accessToken): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get('https://api.mercadopago.com/users/me');

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'valid' => true,
                    'account_info' => [
                        'id' => $data['id'] ?? null,
                        'email' => $data['email'] ?? null,
                        'nickname' => $data['nickname'] ?? null,
                        'first_name' => $data['first_name'] ?? null,
                    ],
                    'error' => null,
                ];
            }

            return [
                'valid' => false,
                'account_info' => null,
                'error' => 'Credenciais inválidas ou expiradas.',
            ];

        } catch (\Exception $e) {
            \Log::error('Erro ao validar credenciais do Mercado Pago', [
                'message' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'account_info' => null,
                'error' => 'Erro ao validar credenciais: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retorna as credenciais da plataforma configuradas no .env
     */
    public static function getPlatformCredentials(): array
    {
        $accessToken = config('mercadopago.platform_access_token');
        $publicKey = config('mercadopago.platform_public_key');

        if (empty($accessToken) || empty($publicKey)) {
            throw new \Exception('Credenciais da plataforma não configuradas no .env');
        }

        return [
            'access_token' => $accessToken,
            'public_key' => $publicKey,
        ];
    }
    /**
     * Cria uma preferência de pagamento no Mercado Pago
     */
    public function createPreference(Order $order): array
    {
        try {
            // Busca as credenciais do organizador
            $payoutSetting = $order->event->payoutSetting;
            
            if (!$payoutSetting || $payoutSetting->method !== 'mercadopago') {
                throw new \Exception('Configuração de pagamento não encontrada ou inválida para este evento.');
            }

            $details = $payoutSetting->details;
            if (!isset($details['access_token'])) {
                throw new \Exception('Access token do Mercado Pago não configurado.');
            }

            // Configura o SDK do Mercado Pago com o token do organizador
            MercadoPagoConfig::setAccessToken($details['access_token']);

            // Prepara os itens para o Mercado Pago
            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'id' => (string) $item->ticketType->id,
                    'title' => $item->ticketType->name,
                    'description' => $item->ticketType->description ?? $order->event->title,
                    'category_id' => 'tickets',
                    'quantity' => 1,
                    'currency_id' => $order->currency,
                    'unit_price' => (float) ($item->ticketType->price_cents / 100), // Mercado Pago usa decimais
                ];
            }

            // Cria a preferência
            $client = new PreferenceClient();
            
            $preferenceData = [
                'items' => $items,
                'external_reference' => $order->reference,
                'notification_url' => url('/api/webhooks/mercadopago'),
                'expires' => true,
                'expiration_date_from' => now()->toIso8601String(),
                'expiration_date_to' => now()->addHours(48)->toIso8601String(),
            ];

            // Adiciona statement_descriptor apenas se o nome do evento não estiver vazio
            if (!empty($order->event->title)) {
                $preferenceData['statement_descriptor'] = substr($order->event->title, 0, 22);
            }

            \Log::info('Criando preferência no Mercado Pago', [
                'preference_data' => $preferenceData,
            ]);
            
            $preference = $client->create($preferenceData);

            return [
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point,
                'preference_id' => $preference->id,
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao criar preferência no Mercado Pago', [
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
                'api_response' => $e->getApiResponse(),
            ]);
            
            throw new \Exception('Erro ao criar link de pagamento: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Erro ao criar preferência no Mercado Pago', [
                'message' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Busca informações de um pagamento
     */
    public function getPayment(string $paymentId, string $accessToken): ?array
    {
        try {
            MercadoPagoConfig::setAccessToken($accessToken);
            
            $client = new \MercadoPago\Client\Payment\PaymentClient();
            $payment = $client->get($paymentId);

            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'external_reference' => $payment->external_reference,
                'transaction_amount' => $payment->transaction_amount,
                'payment_method_id' => $payment->payment_method_id,
                'payment_type_id' => $payment->payment_type_id,
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao buscar pagamento no Mercado Pago', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}
