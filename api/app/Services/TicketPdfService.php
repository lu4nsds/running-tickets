<?php

namespace App\Services;

use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TicketPdfService
{
    /**
     * Gera um PDF do ticket com as informações do participante e QR code
     *
     * @param OrderItem $orderItem
     * @return string Caminho do arquivo PDF gerado
     */
    public function generateTicketPdf(OrderItem $orderItem): string
    {
        // Carrega as relações necessárias
        $orderItem->load([
            'order.event',
            'ticket',
            'ticketType',
            'category'
        ]);

        // Gera o QR code em base64 para embutir no PDF
        $qrCodeBase64 = $this->generateQrCodeBase64($orderItem->ticket->code);

        // Renderiza o PDF com a view
        $pdf = Pdf::loadView('pdfs.ticket', [
            'orderItem' => $orderItem,
            'order' => $orderItem->order,
            'event' => $orderItem->order->event,
            'ticket' => $orderItem->ticket,
            'ticketType' => $orderItem->ticketType,
            'category' => $orderItem->category,
            'participantName' => $orderItem->participant_data['name'] ?? 'Participante',
            'participantEmail' => $orderItem->participant_data['email'] ?? '',
            'participantCpf' => $orderItem->participant_data['cpf'] ?? '',
            'qrCodeBase64' => $qrCodeBase64,
        ]);

        // Configurações do PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', false);

        // Define o nome do arquivo
        $fileName = 'ticket-' . $orderItem->ticket->code . '.pdf';
        $filePath = 'tickets/' . $fileName;

        // Salva o PDF no storage
        Storage::put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Gera o QR code em formato base64 para embutir no PDF
     *
     * @param string $code
     * @return string
     */
    private function generateQrCodeBase64(string $code): string
    {
        // Gera QR code como SVG (nativamente suportado pelo dompdf, sem precisar de imagick)
        $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($code);

        // Retorna o SVG diretamente (dompdf suporta SVG inline)
        return 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
    }

    /**
     * Remove PDFs temporários gerados
     *
     * @param array $filePaths
     * @return void
     */
    public function cleanupTempPdfs(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }
    }
}
