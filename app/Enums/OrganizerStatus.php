<?php

namespace App\Enums;

/**
 * Status do organizador
 */
enum OrganizerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

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
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
        };
    }
}
