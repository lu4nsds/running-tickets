<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppTicketNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 30;

    public function __construct(public Order $order) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $this->order->load(['items.ticket', 'items.ticketType', 'event']);

        $event    = $this->order->event;
        $buyerPhone = $this->order->buyer_phone;
        $eventDate  = $event->date_start
            ? $event->date_start->format('d/m/Y \à\s H:i')
            : 'a confirmar';
        $location   = implode(' - ', array_filter([$event->venue, $event->city, $event->state]));

        // 1. Envia para o COMPRADOR com TODOS os ingressos
        if ($buyerPhone) {
            $buyerMessage = $this->buildBuyerMessage($event->title, $eventDate, $location);
            $sent = $whatsApp->send($buyerPhone, $buyerMessage);

            Log::info('WhatsApp comprador', [
                'order_id'    => $this->order->id,
                'buyer_phone' => $buyerPhone,
                'sent'        => $sent,
            ]);
        }

        // 2. Envia para cada PARTICIPANTE com somente o seu ingresso
        foreach ($this->order->items as $item) {
            $participantPhone = $item->participant_data['phone'] ?? null;

            if (!$participantPhone || $participantPhone === $buyerPhone) {
                // Mesmo número do comprador: mensagem de comprador já cobre
                if ($participantPhone === $buyerPhone) {
                    Log::info('WhatsApp participante é comprador, já notificado', [
                        'order_item_id' => $item->id,
                    ]);
                }
                continue;
            }

            $participantName = $item->participant_data['name'] ?? 'Participante';
            $ticketCode      = $item->ticket?->code ?? 'N/A';

            $participantMessage = $this->buildParticipantMessage(
                $participantName,
                $event->title,
                $eventDate,
                $location,
                $ticketCode,
            );

            $sent = $whatsApp->send($participantPhone, $participantMessage);

            Log::info('WhatsApp participante', [
                'order_id'         => $this->order->id,
                'order_item_id'    => $item->id,
                'participant_phone' => $participantPhone,
                'sent'             => $sent,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsAppTicketNotificationJob falhou', [
            'order_id' => $this->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }

    private function buildBuyerMessage(string $eventTitle, string $eventDate, string $location): string
    {
        $lines = ["🎽 *Seu pedido foi confirmado!*", ''];
        $lines[] = "Evento: *{$eventTitle}*";
        $lines[] = "📅 {$eventDate}";

        if ($location) {
            $lines[] = "📍 {$location}";
        }

        $lines[] = '';
        $lines[] = '*Seus ingressos:*';

        $number = 1;
        foreach ($this->order->items as $item) {
            $name = $item->participant_data['name'] ?? 'Participante';
            $code = $item->ticket?->code ?? 'N/A';
            $lines[] = "{$number}. {$name} — Código: `{$code}`";
            $number++;
        }

        $lines[] = '';
        $lines[] = 'Apresente o código ou o QR Code no dia do evento.';
        $lines[] = 'Boas corridas! 🏃';

        return implode("\n", $lines);
    }

    private function buildParticipantMessage(
        string $name,
        string $eventTitle,
        string $eventDate,
        string $location,
        string $ticketCode,
    ): string {
        $lines = ["🎽 *Olá, {$name}!*", ''];
        $lines[] = "Seu ingresso para *{$eventTitle}* foi confirmado!";
        $lines[] = '';
        $lines[] = "📅 {$eventDate}";

        if ($location) {
            $lines[] = "📍 {$location}";
        }

        $lines[] = "🎫 Código: `{$ticketCode}`";
        $lines[] = '';
        $lines[] = 'Apresente este código ou o QR Code no dia do evento.';
        $lines[] = 'Boas corridas! 🏃';

        return implode("\n", $lines);
    }
}
