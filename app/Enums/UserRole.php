<?php

namespace App\Enums;

/**
 * Roles globais do sistema
 * Definidos na tabela 'roles'
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case USER = 'user';

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
            self::SUPER_ADMIN => 'Super Administrador',
            self::USER => 'Usuário',
        };
    }
}
