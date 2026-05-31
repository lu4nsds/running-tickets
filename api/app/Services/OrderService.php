<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Janela de reserva de estoque entre a criação do pedido e o pagamento.
     * Após esse tempo, o ExpireUnpaidOrdersJob libera os ingressos.
     */
    public const RESERVATION_MINUTES = 15;

    /**
     * Verifica disponibilidade de forma otimista (sem lock), usado na entrada
     * de fluxos como processPayment(). O lock pesado fica para o caminho
     * crítico (criação do Order e job de cartão).
     *
     * @throws \RuntimeException quando algum item ultrapassa a quota.
     */
    public function assertAvailability(Order $order): void
    {
        $order->loadMissing('items');

        $countsByTicketType = $order->items
            ->groupBy('ticket_type_id')
            ->map(fn ($items) => $items->count());

        $ticketTypes = TicketType::whereIn('id', $countsByTicketType->keys())->get()->keyBy('id');

        foreach ($countsByTicketType as $ticketTypeId => $requested) {
            $ticketType = $ticketTypes[$ticketTypeId] ?? null;

            if (!$ticketType) {
                throw new \RuntimeException("Tipo de ingresso ID {$ticketTypeId} não encontrado.");
            }

            $available = $ticketType->getAvailableQuantity();

            if ($available !== null && $requested > $available) {
                throw new \RuntimeException("Ingresso '{$ticketType->name}' esgotado.");
            }
        }
    }

    /**
     * Mesma checagem com lockForUpdate em transação — usado dentro do
     * ProcessCardPaymentJob como barreira final contra overselling.
     */
    public function assertAvailabilityLocked(Order $order): void
    {
        $order->loadMissing('items');
        $ticketTypeIds = $order->items->pluck('ticket_type_id')->unique();

        DB::transaction(function () use ($order, $ticketTypeIds) {
            $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)->lockForUpdate()->get();

            foreach ($ticketTypes as $ticketType) {
                $requested = $order->items->where('ticket_type_id', $ticketType->id)->count();
                $available = $ticketType->getAvailableQuantity();

                if ($available !== null && $requested > $available) {
                    throw new \RuntimeException("Ingresso '{$ticketType->name}' esgotado.");
                }
            }
        });
    }

    public function reservationExpiry(): \Illuminate\Support\Carbon
    {
        return now()->addMinutes(self::RESERVATION_MINUTES);
    }
}
