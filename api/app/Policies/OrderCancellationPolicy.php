<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderCancellationPolicy
{
    /**
     * Quem pode criar uma solicitação de cancelamento para um pedido.
     *
     * Apenas o dono do pedido, somente para pedidos pagos e sem outra
     * solicitação ainda pendente.
     */
    public function create(User $user, Order $order): bool
    {
        return $order->user_id === $user->id
            && $order->isPaid()
            && ! $order->hasPendingCancellation();
    }

    /**
     * Quem pode avaliar (aprovar/rejeitar) solicitações de cancelamento.
     *
     * Apenas super admin.
     */
    public function review(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
