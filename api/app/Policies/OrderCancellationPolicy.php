<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\User;

class OrderCancellationPolicy
{
    /**
     * Quem pode criar uma solicitação de cancelamento para um pedido.
     *
     * Apenas o dono do pedido, e a elegibilidade segue a regra de negócio
     * única em Order::canRequestCancellation() (pago, < 7 dias, ingresso
     * ativo, sem solicitação pendente/aprovada).
     */
    public function create(User $user, Order $order): bool
    {
        return $order->user_id === $user->id
            && $order->canRequestCancellation();
    }

    /**
     * Quem pode avaliar (aprovar/rejeitar) solicitações de cancelamento.
     *
     * Apenas super admin (backoffice).
     */
    public function review(AdminUser $user): bool
    {
        return $user->isSuperAdmin();
    }
}
