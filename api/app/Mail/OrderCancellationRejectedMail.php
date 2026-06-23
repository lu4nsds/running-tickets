<?php

namespace App\Mail;

use App\Models\OrderCancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancellationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrderCancellation $cancellation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitação de cancelamento não aprovada - '.$this->cancellation->order->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.cancellation-rejected',
            with: [
                'cancellation' => $this->cancellation,
                'order' => $this->cancellation->order,
                'reason' => $this->cancellation->review_notes,
            ],
        );
    }
}
