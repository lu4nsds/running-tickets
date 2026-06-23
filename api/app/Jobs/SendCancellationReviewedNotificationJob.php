<?php

namespace App\Jobs;

use App\Enums\OrderCancellationStatus;
use App\Mail\OrderCancellationApprovedMail;
use App\Mail\OrderCancellationRejectedMail;
use App\Models\OrderCancellation;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCancellationReviewedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public OrderCancellation $cancellation,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $this->cancellation->loadMissing(['order.event']);

        $order = $this->cancellation->order;
        $status = $this->cancellation->status;

        // Notifica apenas estados avaliados (aprovado/rejeitado).
        if (! in_array($status, [OrderCancellationStatus::APPROVED, OrderCancellationStatus::REJECTED], true)) {
            return;
        }

        $this->sendEmail($order, $status);
        $this->sendWhatsApp($whatsApp, $order, $status);
    }

    private function sendEmail($order, OrderCancellationStatus $status): void
    {
        $email = $order->buyer_email;

        if (! $email) {
            return;
        }

        $mailable = $status === OrderCancellationStatus::APPROVED
            ? new OrderCancellationApprovedMail($this->cancellation)
            : new OrderCancellationRejectedMail($this->cancellation);

        Mail::to($email)->queue($mailable);

        Log::info('E-mail de avaliação de cancelamento enviado', [
            'cancellation_id' => $this->cancellation->id,
            'order_id' => $order->id,
            'status' => $status->value,
            'buyer_email' => $email,
        ]);
    }

    private function sendWhatsApp(WhatsAppService $whatsApp, $order, OrderCancellationStatus $status): void
    {
        $phone = $order->buyer_phone;

        if (! $phone) {
            return;
        }

        if ($status === OrderCancellationStatus::APPROVED) {
            $message = __('whatsapp.cancellation_approved', [
                'reference' => $order->reference,
                'event' => $order->event->title,
                'amount' => 'R$ '.number_format($order->total_cents / 100, 2, ',', '.'),
            ]);
        } else {
            $notes = $this->cancellation->review_notes;
            $message = __('whatsapp.cancellation_rejected', [
                'reference' => $order->reference,
                'event' => $order->event->title,
                'reason' => $notes ? "\n📝 Motivo: {$notes}\n\n" : "\n",
            ]);
        }

        $sent = $whatsApp->send($phone, $message);

        Log::info('WhatsApp de avaliação de cancelamento', [
            'cancellation_id' => $this->cancellation->id,
            'order_id' => $order->id,
            'status' => $status->value,
            'buyer_phone' => $phone,
            'sent' => $sent,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendCancellationReviewedNotificationJob falhou', [
            'cancellation_id' => $this->cancellation->id,
            'error' => $e->getMessage(),
        ]);
    }
}
