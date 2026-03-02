<?php

namespace App\Enums;

/**
 * Moedas suportadas pelo sistema
 */
enum Currency: string
{
    case BRL = 'BRL';

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
            self::BRL => 'Real Brasileiro',
        };
    }

    /**
     * Retorna símbolo da moeda
     */
    public function symbol(): string
    {
        return match($this) {
            self::BRL => 'R$',
        };
    }
}
