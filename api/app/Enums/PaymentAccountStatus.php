<?php

namespace App\Enums;

/**
 * Situação da conexão de um organizador com um gateway de pagamento.
 *
 * CONNECTED → credenciais válidas, pagamentos podem ser criados em nome do organizador.
 * EXPIRED   → o token venceu e a renovação falhou; requer reconexão.
 * REVOKED   → o organizador (ou a plataforma) desfez a autorização.
 */
enum PaymentAccountStatus: string
{
    case CONNECTED = 'connected';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';

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
            self::CONNECTED => 'Conectado',
            self::EXPIRED => 'Expirado',
            self::REVOKED => 'Revogado',
        };
    }
}
