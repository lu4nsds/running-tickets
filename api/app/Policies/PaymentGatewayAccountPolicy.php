<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Organizer;

class PaymentGatewayAccountPolicy
{
    /**
     * Quem pode gerenciar (conectar / desconectar / ver) a conta de gateway de
     * um organizador. Apenas o organizador Admin daquele organizador — staff
     * não pode. Super admin passa direto.
     */
    public function manage(AdminUser $user, Organizer $organizer): bool
    {
        return $user->isSuperAdmin()
            || $user->isOrganizerAdmin($organizer->id);
    }
}
