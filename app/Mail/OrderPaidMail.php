<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pagamento Confirmado - ' . $this->order->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.paid',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        // Anexa os QR codes de todos os tickets
        foreach ($this->order->items as $item) {
            if ($item->ticket && $item->ticket->qr_path) {
                $qrContent = Storage::disk('public')->get($item->ticket->qr_path);
                
                if ($qrContent) {
                    $attachments[] = Attachment::fromData(fn () => $qrContent, 'ticket-' . $item->ticket->code . '.svg')
                        ->withMime('image/svg+xml');
                }
            }
        }

        return $attachments;
    }
}
