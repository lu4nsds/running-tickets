<?php

namespace App\Enums;

/**
 * Roles de usuários dentro de um organizador
 * Definidos na pivot table 'organizer_users.role'
 */
enum OrganizerRole: string
{
    case ADMIN = 'admin';
    case STAFF = 'staff';

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
            self::ADMIN => 'Administrador',
            self::STAFF => 'Equipe',
        };
    }
}
