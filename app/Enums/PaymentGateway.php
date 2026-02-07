<?php

namespace App\Enums;

/**
 * Gateways de pagamento disponíveis
 */
enum PaymentGateway: string
{
    case MERCADOPAGO = 'mercadopago';

    /**
     * Retorna todos os valores possíveis
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna label legível
     */
    public function label(): string
    {
        return match($this) {
            self::MERCADOPAGO => 'Mercado Pago',
        };
    }
}
