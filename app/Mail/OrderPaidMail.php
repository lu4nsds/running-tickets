<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\TicketPdfService;
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

    private array $generatedPdfs = [];

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
        $pdfService = app(TicketPdfService::class);

        // Gera e anexa PDFs de todos os tickets
        foreach ($this->order->items as $item) {
            if ($item->ticket) {
                try {
                    $pdfPath = $pdfService->generateTicketPdf($item);
                    $this->generatedPdfs[] = $pdfPath;
                    
                    $attachments[] = Attachment::fromStorage($pdfPath)
                        ->as('ingresso-' . $item->participant_data['name'] . '.pdf')
                        ->withMime('application/pdf');
                } catch (\Exception $e) {
                    \Log::error('Erro ao gerar PDF do ticket', [
                        'order_item_id' => $item->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $attachments;
    }

    /**
     * Limpa PDFs temporários após o envio
     */
    public function __destruct()
    {
        if (!empty($this->generatedPdfs)) {
            $pdfService = app(TicketPdfService::class);
            $pdfService->cleanupTempPdfs($this->generatedPdfs);
        }
    }
}
