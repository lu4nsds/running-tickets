<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pagamento não aprovado - ' . $this->order->event->title,
        );
    }

    public function content(): Content
    {
        $body = $this->order->payment_response_body ?? [];
        $appUrl = rtrim(config('app.client_url') ?? config('app.url'), '/');

        return new Content(
            view: 'emails.orders.payment-failed',
            with: [
                'order' => $this->order,
                'failureMessage' => $body['failure_message_pt'] ?? 'Não foi possível processar seu pagamento.',
                'failureReason' => $body['failure_reason'] ?? null,
                'paymentUrl' => $appUrl . '/pagamento/' . $this->order->reference,
                'reservedUntil' => $this->order->reserved_until,
            ],
        );
    }
}
