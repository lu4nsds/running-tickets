<?php

namespace App\Enums;

enum TicketStatus: string
{
    case ACTIVE = 'active';
    case USED = 'used';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Ativo',
            self::USED => 'Utilizado',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::USED => 'info',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'warning',
        };
    }
}
