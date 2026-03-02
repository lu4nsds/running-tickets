<?php

namespace App\Mail;

use App\Models\OrderItem;
use App\Services\TicketPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ParticipantTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?string $generatedPdf = null;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public OrderItem $orderItem
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu Ingresso - ' . $this->orderItem->order->event->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.participant-ticket',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        $pdfService = app(TicketPdfService::class);

        // Gera e anexa PDF deste ticket
        if ($this->orderItem->ticket) {
            try {
                $this->generatedPdf = $pdfService->generateTicketPdf($this->orderItem);
                
                $attachments[] = Attachment::fromStorage($this->generatedPdf)
                    ->as('ingresso-' . $this->orderItem->participant_data['name'] . '.pdf')
                    ->withMime('application/pdf');
            } catch (\Exception $e) {
                \Log::error('Erro ao gerar PDF do ticket', [
                    'order_item_id' => $this->orderItem->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $attachments;
    }

    /**
     * Limpa PDF temporário após o envio
     */
    public function __destruct()
    {
        if ($this->generatedPdf) {
            $pdfService = app(TicketPdfService::class);
            $pdfService->cleanupTempPdfs([$this->generatedPdf]);
        }
    }
}
