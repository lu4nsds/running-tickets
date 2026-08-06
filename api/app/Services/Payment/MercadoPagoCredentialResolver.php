<?php

namespace App\Services\Payment;

use App\Enums\PayoutMode;
use App\Models\Order;
use RuntimeException;

/**
 * Decide, para um pedido, qual credencial do Mercado Pago usar e se há
 * application_fee (split). Fonte única consumida pelo fluxo de pagamento e pelo
 * webhook. Na Parte 2 (multi-gateway) esta lógica é absorvida pelo manager.
 */
class MercadoPagoCredentialResolver
{
    public function resolveForOrder(Order $order): PaymentContext
    {
        $event = $order->event;

        if ($event && $event->usesSplit()) {
            return $this->splitContext($order);
        }

        return $this->platformContext();
    }

    /**
     * Contexto do modo centralizado: credenciais globais da plataforma.
     */
    public function platformContext(): PaymentContext
    {
        return new PaymentContext(
            accessToken: (string) config('mercadopago.access_token'),
            settlementMode: PayoutMode::PLATFORM,
            applicationFeeCents: null,
            publicKey: (string) config('mercadopago.public_key'),
        );
    }

    /**
     * Contexto do split: credenciais do organizador + comissão da plataforma.
     */
    private function splitContext(Order $order): PaymentContext
    {
        $account = $order->event->organizer->paymentAccount;

        if (! $account || ! $account->isConnected()) {
            throw new RuntimeException(
                'Organizador sem conta do Mercado Pago conectada para o split.'
            );
        }

        $feeCents = (int) round($order->total_cents * $order->event->effectiveFeeRate());

        return new PaymentContext(
            accessToken: (string) $account->access_token,
            settlementMode: PayoutMode::SPLIT,
            applicationFeeCents: $feeCents,
            publicKey: $account->public_key,
        );
    }
}
