<?php

namespace App\Jobs;

use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateOrderTicketsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Gerando tickets para o pedido', [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
        ]);

        // Carrega os itens se não estiverem carregados
        if (!$this->order->relationLoaded('items')) {
            $this->order->load('items');
        }

        foreach ($this->order->items as $item) {
            // Verifica se já existe um ticket para este item
            if ($item->ticket) {
                Log::info('Ticket já existe para o item', [
                    'order_item_id' => $item->id,
                    'ticket_id' => $item->ticket->id,
                ]);
                continue;
            }

            // Gera um código único para o ticket
            $code = Ticket::generateCode();

            // Cria o ticket
            $ticket = Ticket::create([
                'order_item_id' => $item->id,
                'code' => $code,
                'status' => TicketStatus::ACTIVE,
                'issued_at' => now(),
            ]);

            // Gera o QR Code
            $this->generateQrCode($ticket);

            Log::info('Ticket gerado com sucesso', [
                'ticket_id' => $ticket->id,
                'code' => $code,
            ]);
        }

        // Após gerar todos os tickets, envia email de confirmação
        $this->sendConfirmationEmail();
    }

    /**
     * Gera o QR Code para o ticket
     */
    private function generateQrCode(Ticket $ticket): void
    {
        try {
            // Gera a URL do ticket (será usada para validação)
            $ticketUrl = config('app.url') . '/api/tickets/' . $ticket->code;

            // Gera o QR Code como SVG (não precisa de extensões de imagem)
            $qrCode = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($ticketUrl);

            // Define o caminho do arquivo
            $path = 'tickets/' . $ticket->code . '.svg';

            // Salva o arquivo no storage
            Storage::disk('public')->put($path, $qrCode);

            // Atualiza o ticket com o caminho do QR Code
            $ticket->update([
                'qr_path' => $path,
            ]);

            Log::info('QR Code gerado com sucesso', [
                'ticket_id' => $ticket->id,
                'path' => $path,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar QR Code', [
                'ticket_id' => $ticket->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envia email de confirmação com os tickets
     */
    private function sendConfirmationEmail(): void
    {
        // Carrega as relações necessárias
        $this->order->load(['items.ticket', 'event']);

        // Pega email do primeiro participante
        $email = $this->order->items->first()->participant_data['email'] ?? null;

        if ($email) {
            \Illuminate\Support\Facades\Mail::to($email)
                ->queue(new \App\Mail\OrderPaidMail($this->order));

            Log::info('Email de confirmação enviado', [
                'order_id' => $this->order->id,
                'email' => $email,
            ]);
        }
    }
}
