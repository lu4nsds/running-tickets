<?php

namespace App\Services\Payment;

/**
 * Contexto resolvido para criar/consultar um pagamento de um pedido: qual
 * credencial usar (conta da plataforma ou do organizador) e, no split, quanto
 * a plataforma retém via application_fee.
 */
class PaymentContext
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $settlementMode,
        public readonly ?int $applicationFeeCents = null,
        public readonly ?string $publicKey = null,
    ) {}

    public function isSplit(): bool
    {
        return $this->settlementMode === \App\Enums\PayoutMode::SPLIT->value;
    }

    /**
     * application_fee em unidades de moeda (reais), como o Mercado Pago espera.
     */
    public function applicationFeeAmount(): ?float
    {
        return $this->applicationFeeCents !== null
            ? $this->applicationFeeCents / 100
            : null;
    }
}
