<?php

namespace App\Enums;

/**
 * Status de pagamento do Mercado Pago
 * Baseado na documentação oficial: https://www.mercadopago.com.br/developers/pt/docs/checkout-api/payment-status
 */
enum MercadoPagoStatus: string
{
    case APPROVED = 'approved';
    case REFUNDED = 'refunded';
    case CHARGED_BACK = 'charged_back';
    case CANCELLED = 'cancelled';

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
            self::APPROVED => 'Aprovado',
            self::REFUNDED => 'Reembolsado',
            self::CHARGED_BACK => 'Chargeback',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Mapeia status do MercadoPago para OrderStatus
     */
    public function toOrderStatus(): OrderStatus
    {
        return match($this) {
            self::APPROVED => OrderStatus::PAID,
            self::REFUNDED, self::CHARGED_BACK => OrderStatus::REFUNDED,
            self::CANCELLED => OrderStatus::CANCELLED,
        };
    }
}
