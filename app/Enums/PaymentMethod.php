<?php

namespace App\Enums;

/**
 * Métodos de pagamento/recebimento
 */
enum PaymentMethod: string
{
    case PIX = 'pix';
    case BANK_ACCOUNT = 'bank_account';
    case GATEWAY = 'gateway';
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
            self::PIX => 'PIX',
            self::BANK_ACCOUNT => 'Conta Bancária',
            self::GATEWAY => 'Gateway',
            self::MERCADOPAGO => 'Mercado Pago',
        };
    }
}
