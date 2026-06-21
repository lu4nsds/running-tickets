<?php

namespace App\Services;

use App\Enums\OrderCancellationStatus;
use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private PaymentResultService $paymentResultService,
    ) {}

    /**
     * Aprova a solicitação e executa o estorno integral no gateway.
     *
     * Tudo dentro de uma transação: se o Mercado Pago recusar o estorno, a
     * exception propaga, a transação faz rollback e a solicitação NÃO é marcada
     * como aprovada.
     */
    public function approve(OrderCancellation $request, User $reviewer): OrderCancellation
    {
        $order = $request->order;

        return DB::transaction(function () use ($request, $order, $reviewer) {
            // 1. Estorno no Mercado Pago (lança em caso de falha)
            $refund = $this->mercadoPagoService->refundPayment((string) $order->payment_id);

            // 2. Atualiza status e payment_response_body do pedido (REFUNDED)
            $this->paymentResultService->apply($order, [
                'status' => 'refunded',
                'status_detail' => 'Estorno aprovado pelo administrador',
            ]);

            // 3. Marca os ingressos do pedido como reembolsados
            Ticket::whereIn('order_item_id', $order->items()->select('id'))
                ->update(['status' => TicketStatus::REFUNDED->value]);

            // 4. Atualiza a solicitação
            $request->update([
                'status' => OrderCancellationStatus::APPROVED,
                'refund_id' => isset($refund['id']) ? (string) $refund['id'] : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            // 5. Invalida caches de dashboard (refund altera receita)
            $this->flushDashboardCache($order);

            return $request->fresh(['order']);
        });
    }

    /**
     * Rejeita a solicitação sem tocar no gateway e reativa os ingressos que
     * haviam sido desativados enquanto a solicitação estava pendente.
     */
    public function reject(OrderCancellation $request, User $reviewer, ?string $notes): OrderCancellation
    {
        return DB::transaction(function () use ($request, $reviewer, $notes) {
            $request->update([
                'status' => OrderCancellationStatus::REJECTED,
                'review_notes' => $notes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->reactivateTickets($request->order);

            return $request->fresh(['order']);
        });
    }

    /**
     * Desativa os ingressos ativos de um pedido enquanto há uma solicitação de
     * cancelamento pendente (impede validação até a avaliação da organização).
     */
    public function deactivateTickets(Order $order): void
    {
        Ticket::whereIn('order_item_id', $order->items()->select('id'))
            ->where('status', TicketStatus::ACTIVE->value)
            ->update(['status' => TicketStatus::INACTIVE->value]);
    }

    /**
     * Reativa os ingressos que foram desativados por uma solicitação rejeitada.
     */
    public function reactivateTickets(Order $order): void
    {
        Ticket::whereIn('order_item_id', $order->items()->select('id'))
            ->where('status', TicketStatus::INACTIVE->value)
            ->update(['status' => TicketStatus::ACTIVE->value]);
    }

    private function flushDashboardCache($order): void
    {
        $order->loadMissing('event');
        $organizerId = $order->organizer_id;

        Cache::forget("dashboard_organizer_{$organizerId}");
        Cache::forget("dashboard_event_{$order->event_id}");
        Cache::forget('dashboard_admin_platform');
        Cache::forget("dashboard_admin_organizer_{$organizerId}");
    }
}
