<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'info',
        };
    }
}
