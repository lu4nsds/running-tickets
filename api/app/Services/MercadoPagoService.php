<?php

namespace App\Services;

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Exceptions\MPApiException;

class MercadoPagoService
{
    private function accessToken(): string
    {
        $token = config('mercadopago.access_token');

        if (empty($token)) {
            throw new \Exception('MERCADOPAGO_ACCESS_TOKEN não configurado no .env');
        }

        return $token;
    }

    private function requestOptions(): RequestOptions
    {
        $opts = new RequestOptions();
        $opts->setAccessToken($this->accessToken());
        return $opts;
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
        string $externalReference
    ): array {
        try {
            $client  = new PaymentClient();
            
            unset($payer['phone']);
            $payment = $client->create([
                'transaction_amount' => (float) ($amountCents / 100),
                'token'              => $token,
                'description'        => 'Running Tickets - Pedido ' . $externalReference,
                'installments'       => $installments,
                'payment_method_id'  => $paymentMethodId,
                'external_reference' => $externalReference,
                'payer'              => $payer,
            ], $this->requestOptions());

            return [
                'id'                  => $payment->id,
                'status'              => $payment->status,
                'status_detail'       => $payment->status_detail,
                'external_reference'  => $payment->external_reference ?? null,
                'transaction_amount'  => $payment->transaction_amount,
                'payment_method_id'   => $payment->payment_method_id,
                'payment_type_id'     => $payment->payment_type_id,
                'installments'        => $payment->installments ?? 1,
                'transaction_details' => [
                    'net_received_amount' => $payment->transaction_details->net_received_amount ?? null,
                    'total_paid_amount'   => $payment->transaction_details->total_paid_amount ?? null,
                ],
                'payer' => [
                    'email'          => $payment->payer->email ?? null,
                    'identification' => [
                        'type'   => $payment->payer->identification->type ?? null,
                        'number' => $payment->payer->identification->number ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao criar pagamento com cartão no Mercado Pago', [
                'external_reference' => $externalReference,
                'message'            => $e->getMessage(),
                'status_code'        => $e->getStatusCode(),
                'api_response'       => $e->getApiResponse()?->getContent(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erro ao criar pagamento com cartão no Mercado Pago', [
                'external_reference' => $externalReference,
                'message'            => $e->getMessage(),
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
        string $externalReference
    ): array {
        try {
            $client   = new PaymentClient();
            $appUrl   = config('app.url');
            $isLocal  = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1');

            unset($payer['phone']);
            $payload = [
                'transaction_amount' => (float) ($amountCents / 100),
                'description'        => 'Running Tickets - Pedido ' . $externalReference,
                'payment_method_id'  => 'pix',
                'external_reference' => $externalReference,
                'payer'              => $payer,
            ];

            if (!$isLocal) {
                $payload['notification_url'] = $appUrl . '/api/webhooks/mercadopago';
            }

            $payment = $client->create($payload, $this->requestOptions());

            return [
                'id'                  => $payment->id,
                'status'              => $payment->status,
                'status_detail'       => $payment->status_detail,
                'external_reference'  => $payment->external_reference ?? null,
                'transaction_amount'  => $payment->transaction_amount,
                'payment_method_id'   => 'pix',
                'payment_type_id'     => 'bank_transfer',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code'        => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                        'ticket_url'     => $payment->point_of_interaction->transaction_data->ticket_url ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao criar pagamento PIX no Mercado Pago', [
                'external_reference' => $externalReference,
                'message'            => $e->getMessage(),
                'status_code'        => $e->getStatusCode(),
                'api_response'       => $e->getApiResponse()?->getContent(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erro ao criar pagamento PIX no Mercado Pago', [
                'external_reference' => $externalReference,
                'message'            => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Busca informações de um pagamento pelo ID
     */
    public function getPaymentById(string $paymentId): ?array
    {
        try {
            $client  = new PaymentClient();
            $payment = $client->get($paymentId, $this->requestOptions());

            return [
                'id'                  => $payment->id,
                'status'              => $payment->status,
                'status_detail'       => $payment->status_detail,
                'external_reference'  => $payment->external_reference ?? null,
                'transaction_amount'  => $payment->transaction_amount,
                'payment_method_id'   => $payment->payment_method_id,
                'payment_type_id'     => $payment->payment_type_id,
                'installments'        => $payment->installments ?? 1,
                'transaction_details' => [
                    'net_received_amount' => $payment->transaction_details->net_received_amount ?? null,
                    'total_paid_amount'   => $payment->transaction_details->total_paid_amount ?? null,
                ],
                'payer' => [
                    'email'          => $payment->payer->email ?? null,
                    'identification' => [
                        'type'   => $payment->payer->identification->type ?? null,
                        'number' => $payment->payer->identification->number ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            \Log::error('Erro ao buscar pagamento no Mercado Pago', [
                'payment_id'  => $paymentId,
                'message'     => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);
            return null;
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar pagamento no Mercado Pago', [
                'payment_id' => $paymentId,
                'message'    => $e->getMessage(),
            ]);
            return null;
        }
    }
}
