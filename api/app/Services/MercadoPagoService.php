<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\EventPayoutSetting;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Common\RequestOptions;
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
     * Retorna a public key do organizador do evento para uso no frontend (Bricks)
     */
    public function getPublicKey(EventPayoutSetting $payoutSetting): string
    {
        if (!$payoutSetting || $payoutSetting->method !== 'mercadopago') {
            throw new \Exception('Configuração de pagamento não encontrada ou inválida para este evento.');
        }

        if ($payoutSetting->payout_mode === 'platform') {
            $publicKey = config('mercadopago.platform_public_key');
            if (empty($publicKey)) {
                throw new \Exception('Public key da plataforma não configurada no .env.');
            }
            return $publicKey;
        }

        $details = $payoutSetting->details;
        if (!isset($details['public_key'])) {
            throw new \Exception('Public key do Mercado Pago não configurada.');
        }

        return $details['public_key'];
    }

    /**
     * Cria um pagamento com token de cartão (Checkout Transparente)
     */
    public function createCardPayment(
        string $token,
        int $amountCents,
        string $paymentMethodId,
        int $installments,
        array $payer,
        string $externalReference,
        string $accessToken
    ): array {
        try {
            $requestOptions = new RequestOptions();
            $requestOptions->setAccessToken($accessToken);

            $client = new PaymentClient();
            $payment = $client->create([
                'transaction_amount' => (float) ($amountCents / 100),
                'token' => $token,
                'description' => 'Running Tickets - Pedido ' . $externalReference,
                'installments' => $installments,
                'payment_method_id' => $paymentMethodId,
                'external_reference' => $externalReference,
                'payer' => $payer,
            ], $requestOptions);

            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'external_reference' => $payment->external_reference ?? null,
                'transaction_amount' => $payment->transaction_amount,
                'payment_method_id' => $payment->payment_method_id,
                'payment_type_id' => $payment->payment_type_id,
                'installments' => $payment->installments ?? 1,
                'transaction_details' => [
                    'net_received_amount' => $payment->transaction_details->net_received_amount ?? null,
                    'total_paid_amount'   => $payment->transaction_details->total_paid_amount ?? null,
                ],
                'payer' => [
                    'email' => $payment->payer->email ?? null,
                    'identification' => [
                        'type' => $payment->payer->identification->type ?? null,
                        'number' => $payment->payer->identification->number ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao criar pagamento com cartão no Mercado Pago', [
                'external_reference' => $externalReference,
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erro ao criar pagamento com cartão no Mercado Pago', [
                'external_reference' => $externalReference,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cria um pagamento via PIX (Checkout Transparente)
     */
    public function createPixPayment(
        int $amountCents,
        array $payer,
        string $externalReference,
        string $accessToken
    ): array {
        try {
            $requestOptions = new RequestOptions();
            $requestOptions->setAccessToken($accessToken);

            $client = new PaymentClient();
            $appUrl = config('app.url');
            $isLocalhost = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1');

            $payload = [
                'transaction_amount' => (float) ($amountCents / 100),
                'description' => 'Running Tickets - Pedido ' . $externalReference,
                'payment_method_id' => 'pix',
                'external_reference' => $externalReference,
                'payer' => $payer,
            ];

            if (!$isLocalhost) {
                $payload['notification_url'] = $appUrl . '/api/webhooks/mercadopago';
            }

            $payment = $client->create($payload, $requestOptions);

            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'external_reference' => $payment->external_reference ?? null,
                'transaction_amount' => $payment->transaction_amount,
                'payment_method_id' => 'pix',
                'payment_type_id' => 'bank_transfer',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                        'ticket_url' => $payment->point_of_interaction->transaction_data->ticket_url ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao criar pagamento PIX no Mercado Pago', [
                'external_reference' => $externalReference,
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
                'api_response' => $e->getApiResponse()?->getContent(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erro ao criar pagamento PIX no Mercado Pago', [
                'external_reference' => $externalReference,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Busca informações de um pagamento pelo ID
     */
    public function getPaymentById(string $paymentId, string $accessToken): ?array
    {
        try {
            $requestOptions = new RequestOptions();
            $requestOptions->setAccessToken($accessToken);

            $client = new PaymentClient();
            $payment = $client->get($paymentId, $requestOptions);

            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'external_reference' => $payment->external_reference ?? null,
                'transaction_amount' => $payment->transaction_amount,
                'payment_method_id' => $payment->payment_method_id,
                'payment_type_id' => $payment->payment_type_id,
                'installments' => $payment->installments ?? 1,
                'transaction_details' => [
                    'net_received_amount' => $payment->transaction_details->net_received_amount ?? null,
                    'total_paid_amount'   => $payment->transaction_details->total_paid_amount ?? null,
                ],
                'payer' => [
                    'email' => $payment->payer->email ?? null,
                    'identification' => [
                        'type' => $payment->payer->identification->type ?? null,
                        'number' => $payment->payer->identification->number ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao buscar pagamento no Mercado Pago', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);
            
            return null;
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar pagamento no Mercado Pago', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}
