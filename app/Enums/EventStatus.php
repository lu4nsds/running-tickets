<?php

namespace App\Enums;

/**
 * Status do evento - controle manual
 * Permite ao organizador suspender temporariamente um evento
 */
enum EventStatus: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';

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
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
        };
    }

    /**
     * Retorna se o evento pode receber novos pedidos
     */
    public function canAcceptOrders(): bool
    {
        return $this === self::ATIVO;
    }
}
