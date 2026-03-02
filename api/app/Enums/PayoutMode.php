<?php

namespace App\Enums;

/**
 * Modo de repasse de pagamento
 */
enum PayoutMode: string
{
    case DIRECT = 'direct';
    case PLATFORM = 'platform';

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
            self::DIRECT => 'Direto ao Organizador',
            self::PLATFORM => 'Gerenciado pela Plataforma',
        };
    }

    /**
     * Retorna se a plataforma cobra taxa
     */
    public function platformChargesFee(): bool
    {
        return $this === self::PLATFORM;
    }
}
