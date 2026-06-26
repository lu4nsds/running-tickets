<?php

namespace App\Enums;

/**
 * Status do evento - controle manual
 * Permite ao organizador suspender temporariamente um evento ou marcá-lo como encerrado
 */
enum EventStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case FINISHED = 'finished';

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
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::FINISHED => 'Encerrado',
        };
    }

    /**
     * Retorna se o evento pode receber novos pedidos
     */
    public function canAcceptOrders(): bool
    {
        return $this === self::ACTIVE;
    }
}
