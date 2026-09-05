<?php

namespace App\Jobs;

use App\Mail\FeedbackRequestMail;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envia ao comprador o convite para responder o formulário de feedback,
 * 24h após a validação do primeiro ingresso do pedido.
 *
 * A deduplicação é por pedido (`orders.feedback_sent_at`): um pedido com
 * vários ingressos gera um único envio.
 */
class SendOrderFeedbackJob implements ShouldQueue
{
    use Queueable;

    // Uma tentativa só: o e-mail já pode ter saído quando algo falha depois,
    // e reexecutar o job inteiro duplicaria o envio. Falhou, `failed()` devolve
    // o pedido à fila de elegíveis e o próximo run do agendador tenta de novo.
    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(
        public Order $order,
        public string $formUrl,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $order = $this->order->fresh();

        if ($order === null) {
            return;
        }

        // O pedido já chega aqui reivindicado pelo command
        // (`feedback_sent_at` preenchido), então não há o que marcar.
        $order->loadMissing('event');

        $this->sendEmail($order);
        $this->sendWhatsApp($whatsApp, $order);
    }

    private function sendEmail(Order $order): void
    {
        $email = $order->buyer_email;

        if (! $email) {
            return;
        }

        Mail::to($email)->queue(new FeedbackRequestMail($order, $this->formUrl));

        Log::info('E-mail de feedback enviado', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'buyer_email' => $email,
        ]);
    }

    private function sendWhatsApp(WhatsAppService $whatsApp, Order $order): void
    {
        $phone = $order->buyer_phone;

        if (! $phone) {
            return;
        }

        $message = __('whatsapp.feedback_request', [
            'event' => $order->event->title,
            'url' => $this->formUrl,
        ]);

        // `send()` nunca lança: falha vira `false` + log, e não deve impedir
        // a marcação do pedido nem o canal de e-mail.
        $sent = $whatsApp->send($phone, $message);

        Log::info('WhatsApp de feedback', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'buyer_phone' => $phone,
            'sent' => $sent,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        // Devolve o pedido à fila de elegíveis para uma nova tentativa no
        // próximo run do agendador.
        Order::query()
            ->whereKey($this->order->getKey())
            ->update(['feedback_sent_at' => null]);

        Log::error('SendOrderFeedbackJob falhou', [
            'order_id' => $this->order->id,
            'error' => $e->getMessage(),
        ]);
    }
}
