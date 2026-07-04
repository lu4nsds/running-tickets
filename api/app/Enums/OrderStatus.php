<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::PAID => 'Pago',
            self::FAILED => 'Falhou',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::PAID => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'info',
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED]);
    }
}
