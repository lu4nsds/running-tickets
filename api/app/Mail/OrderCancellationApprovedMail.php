<?php

namespace App\Mail;

use App\Models\OrderCancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancellationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrderCancellation $cancellation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cancelamento aprovado - '.$this->cancellation->order->event->title,
        );
    }

    public function content(): Content
    {
        $order = $this->cancellation->order;

        return new Content(
            view: 'emails.orders.cancellation-approved',
            with: [
                'cancellation' => $this->cancellation,
                'order' => $order,
                'amount' => 'R$ '.number_format($order->total_cents / 100, 2, ',', '.'),
            ],
        );
    }
}
