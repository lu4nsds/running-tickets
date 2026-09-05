<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $formUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Como foi sua experiência? - '.$this->order->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback.request',
            with: [
                'order' => $this->order,
                'formUrl' => $this->formUrl,
            ],
        );
    }
}
