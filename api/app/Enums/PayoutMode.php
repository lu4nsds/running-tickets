<?php

namespace App\Enums;

/**
 * Modalidade de recebimento de um evento.
 *
 * PLATFORM → 100% do valor cai na conta da plataforma; repasse ao organizador
 *            é feito manualmente depois (comportamento histórico).
 * SPLIT    → split nativo do Mercado Pago: o organizador recebe direto na conta
 *            dele e a plataforma retém a comissão via application_fee.
 */
enum PayoutMode: string
{
    case PLATFORM = 'platform';
    case SPLIT = 'split';

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
            self::PLATFORM => 'Centralizado (plataforma)',
            self::SPLIT => 'Split (organizador recebe direto)',
        };
    }
}
