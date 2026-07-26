<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use MercadoPago\Exceptions\MPApiException;
use Throwable;

class PaymentResultService
{
    /**
     * Aplica a resposta do Mercado Pago em um Order, atualizando status,
     * payment_response_body e disparando e-mails de resultado quando apropriado.
     *
     * Usado pelo ProcessCardPaymentJob, pelo processamento síncrono de PIX
     * e pelo MercadoPagoWebhookController.
     */
    public function apply(Order $order, array $mpResponse): void
    {
        $status = $mpResponse['status'] ?? null;

        // Guard de transição forward-only: o Mercado Pago entrega webhooks
        // at-least-once e fora de ordem. Sem este guard, uma notificação
        // 'pending'/'in_process' atrasada chegando após a aprovação rebaixaria
        // um pedido PAID de volta a PENDING (escondendo ingressos e o botão de
        // cancelamento). Só permitimos transições que avançam o estado.
        if (! $this->canApply($order->status, $status)) {
            // Backfill: um pedido já PAGO pode ter ficado sem fee/net se a
            // primeira aprovação chegou antes de o Mercado Pago liquidar o
            // valor recebido. Uma aprovação posterior traz esses dados — então
            // preenchemos apenas fee_cents/net_amount_cents, sem reprocessar
            // status, ingressos ou e-mails (o guard forward-only segue valendo).
            if ($order->status === OrderStatus::PAID
                && $status === 'approved'
                && $order->net_amount_cents === null) {
                $this->backfillFees($order, $mpResponse);
            }

            Log::info('PaymentResultService: transição ignorada (forward-only)', [
                'order_id' => $order->id,
                'current_status' => $order->status->value,
                'mp_status' => $status,
            ]);

            return;
        }

        match (true) {
            $status === 'approved' => $this->markApproved($order, $mpResponse),
            in_array($status, ['pending', 'in_process', 'authorized'], true) => $this->markPending($order, $mpResponse),
            in_array($status, ['rejected', 'cancelled'], true) => $this->markRejected($order, $mpResponse),
            $status === 'refunded' || $status === 'charged_back' => $this->markRefunded($order, $mpResponse),
            default => Log::info('PaymentResultService: status sem ação', [
                'order_id' => $order->id,
                'mp_status' => $status,
            ]),
        };
    }

    /**
     * Decide se uma notificação do gateway pode ser aplicada ao pedido,
     * considerando seu estado atual. Estados terminais não regridem:
     *
     * - PAID    → só pode avançar para REFUNDED (refunded/charged_back).
     *             Um novo 'approved' é no-op idempotente.
     * - REFUNDED/CANCELLED → terminais, nada é aplicado.
     * - PENDING/PROCESSING/FAILED → ainda em aberto, qualquer status avança.
     */
    private function canApply(OrderStatus $current, ?string $mpStatus): bool
    {
        return match ($current) {
            OrderStatus::PAID => in_array($mpStatus, ['refunded', 'charged_back'], true),
            OrderStatus::REFUNDED, OrderStatus::CANCELLED => false,
            default => true,
        };
    }

    /**
     * Persiste os dados de criação de um PIX no Order, permitindo que o QR seja
     * recuperado em recarregamentos e na tela de Meus Ingressos sem nova
     * chamada ao Mercado Pago.
     */
    public function applyPixCreation(Order $order, array $mpResponse): void
    {
        $transactionData = $mpResponse['point_of_interaction']['transaction_data'] ?? [];

        $body = [
            'outcome' => 'pending',
            'mp_payment_id' => (string) ($mpResponse['id'] ?? ''),
            'mp_status' => $mpResponse['status'] ?? 'pending',
            'mp_status_detail' => $mpResponse['status_detail'] ?? null,
            'payment_method' => 'pix',
            'qr_code' => $transactionData['qr_code'] ?? null,
            'qr_code_base64' => $transactionData['qr_code_base64'] ?? null,
            'ticket_url' => $transactionData['ticket_url'] ?? null,
            'transaction_amount' => $mpResponse['transaction_amount'] ?? null,
            'processed_at' => now()->toIso8601String(),
        ];

        $order->update([
            'status' => OrderStatus::PENDING,
            'payment_gateway' => 'mercadopago',
            'payment_id' => $body['mp_payment_id'] ?: $order->payment_id,
            'payment_response_body' => $body,
        ]);
    }

    /**
     * Marca um pedido como falho a partir de uma exception (timeout, token
     * expirado, MP API error) — usado pelo failed() do ProcessCardPaymentJob.
     */
    public function markFailed(Order $order, ?Throwable $e = null, ?array $mpResponse = null): void
    {
        $reason = $this->classifyFailure($e, $mpResponse);
        $message = $this->failureMessagePt($reason, $mpResponse);

        $body = [
            'outcome' => 'failed',
            'mp_payment_id' => (string) ($mpResponse['id'] ?? $order->payment_id ?? ''),
            'mp_status' => $mpResponse['status'] ?? 'rejected',
            'mp_status_detail' => $mpResponse['status_detail'] ?? ($e?->getMessage() ?? null),
            'failure_reason' => $reason,
            'failure_message_pt' => $message,
            'payment_method' => $mpResponse['payment_method_id'] ?? $order->metadata['payment_method'] ?? null,
            'processed_at' => now()->toIso8601String(),
        ];

        $order->update([
            'status' => OrderStatus::FAILED,
            'payment_gateway' => 'mercadopago',
            'payment_response_body' => $body,
        ]);

        $this->sendFailureEmail($order->fresh());
    }

    /**
     * Extrai fee_cents/net_amount_cents da resposta do Mercado Pago.
     * Retorna [null, null] quando o valor líquido recebido ainda não foi
     * liquidado pelo gateway. Fonte única usada por markApproved e backfillFees.
     *
     * @return array{0: ?int, 1: ?int} [feeCents, netCents]
     */
    private function extractFees(array $mpResponse, Order $order): array
    {
        $netReceived = $mpResponse['transaction_details']['net_received_amount'] ?? null;

        if ($netReceived === null) {
            return [null, null];
        }

        // Valor retido pelo MP entre o bruto e o líquido recebido pela conta.
        $grossFeeCents = (int) round((($mpResponse['transaction_amount'] ?? 0) - $netReceived) * 100);

        // No split, o líquido recebido é o do ORGANIZADOR, então o retido inclui
        // a comissão da plataforma (application_fee). fee_cents deve refletir só
        // a taxa do gateway — descontamos o application_fee. No modo platform o
        // application_fee é null (0), preservando o cálculo atual.
        $appFeeCents = $order->application_fee_cents ?? 0;
        $feeCents = max(0, $grossFeeCents - $appFeeCents);
        $netCents = (int) round($netReceived * 100);

        return [$feeCents, $netCents];
    }

    /**
     * Preenche fee_cents/net_amount_cents de um pedido já PAGO que ficou sem
     * esses valores. Não toca em status nem dispara efeitos colaterais.
     */
    private function backfillFees(Order $order, array $mpResponse): void
    {
        [$feeCents, $netCents] = $this->extractFees($mpResponse, $order);

        if ($netCents === null) {
            return;
        }

        $order->update([
            'fee_cents' => $feeCents,
            'net_amount_cents' => $netCents,
        ]);

        Log::info('PaymentResultService: fee/net preenchidos via backfill', [
            'order_id' => $order->id,
            'fee_cents' => $feeCents,
            'net_amount_cents' => $netCents,
        ]);
    }

    private function markApproved(Order $order, array $mpResponse): void
    {
        [$feeCents, $netCents] = $this->extractFees($mpResponse, $order);
        $netReceived = $mpResponse['transaction_details']['net_received_amount'] ?? null;

        $body = [
            'outcome' => 'approved',
            'mp_payment_id' => (string) ($mpResponse['id'] ?? ''),
            'mp_status' => 'approved',
            'mp_status_detail' => $mpResponse['status_detail'] ?? null,
            'payment_method' => $mpResponse['payment_method_id'] ?? null,
            'payment_type' => $mpResponse['payment_type_id'] ?? null,
            'installments' => $mpResponse['installments'] ?? null,
            'transaction_amount' => $mpResponse['transaction_amount'] ?? null,
            'net_received_amount' => $netReceived,
            'processed_at' => now()->toIso8601String(),
        ];

        $order->update([
            'status' => OrderStatus::PAID,
            'paid_at' => $order->paid_at ?? now(),
            'payment_gateway' => 'mercadopago',
            'payment_id' => $body['mp_payment_id'] ?: $order->payment_id,
            'fee_cents' => $feeCents,
            'net_amount_cents' => $netCents,
            'payment_response_body' => $body,
        ]);
    }

    private function markPending(Order $order, array $mpResponse): void
    {
        $body = array_merge($order->payment_response_body ?? [], [
            'outcome' => 'pending',
            'mp_payment_id' => (string) ($mpResponse['id'] ?? $order->payment_id ?? ''),
            'mp_status' => $mpResponse['status'] ?? 'pending',
            'mp_status_detail' => $mpResponse['status_detail'] ?? null,
            'processed_at' => now()->toIso8601String(),
        ]);

        // Cartão em análise → segue como PROCESSING aguardando webhook.
        // PIX criado → continua PENDING (status inicial).
        $newStatus = $order->status === OrderStatus::PROCESSING
            ? OrderStatus::PROCESSING
            : OrderStatus::PENDING;

        $order->update([
            'status' => $newStatus,
            'payment_gateway' => 'mercadopago',
            'payment_id' => $body['mp_payment_id'] ?: $order->payment_id,
            'payment_response_body' => $body,
        ]);
    }

    private function markRejected(Order $order, array $mpResponse): void
    {
        $this->markFailed($order, null, $mpResponse);
    }

    private function markRefunded(Order $order, array $mpResponse): void
    {
        $body = array_merge($order->payment_response_body ?? [], [
            'outcome' => 'refunded',
            'mp_payment_id' => (string) ($mpResponse['id'] ?? $order->payment_id ?? ''),
            'mp_status' => $mpResponse['status'],
            'mp_status_detail' => $mpResponse['status_detail'] ?? null,
            'processed_at' => now()->toIso8601String(),
        ]);

        $order->update([
            'status' => OrderStatus::REFUNDED,
            'payment_gateway' => 'mercadopago',
            'payment_id' => $body['mp_payment_id'] ?: $order->payment_id,
            'payment_response_body' => $body,
        ]);
    }

    private function classifyFailure(?Throwable $e, ?array $mpResponse): string
    {
        $statusDetail = $mpResponse['status_detail'] ?? null;

        if ($statusDetail && str_starts_with($statusDetail, 'cc_rejected_')) {
            return 'card_declined';
        }

        if ($e instanceof MPApiException) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'token') || str_contains($msg, 'card_token')) {
                return 'token_expired';
            }

            return 'mp_api_error';
        }

        if ($e !== null) {
            return 'internal_error';
        }

        if ($mpResponse['status'] ?? null === 'rejected') {
            return 'card_declined';
        }

        return 'mp_api_error';
    }

    private function failureMessagePt(string $reason, ?array $mpResponse): string
    {
        $statusDetail = $mpResponse['status_detail'] ?? null;

        $detailMessages = [
            'cc_rejected_insufficient_amount' => 'Cartão recusado por saldo insuficiente.',
            'cc_rejected_bad_filled_card_number' => 'Número do cartão inválido. Verifique e tente novamente.',
            'cc_rejected_bad_filled_date' => 'Data de validade do cartão inválida.',
            'cc_rejected_bad_filled_security_code' => 'Código de segurança (CVV) inválido.',
            'cc_rejected_bad_filled_other' => 'Dados do cartão inválidos. Verifique e tente novamente.',
            'cc_rejected_high_risk' => 'Pagamento recusado por suspeita de fraude. Tente outro cartão.',
            'cc_rejected_call_for_authorize' => 'Você precisa autorizar este pagamento junto ao emissor do cartão.',
            'cc_rejected_card_disabled' => 'Cartão desativado. Entre em contato com o emissor.',
            'cc_rejected_duplicated_payment' => 'Pagamento duplicado detectado. Aguarde alguns minutos antes de tentar novamente.',
            'cc_rejected_max_attempts' => 'Limite de tentativas excedido. Tente novamente mais tarde com outro cartão.',
            'cc_rejected_other_reason' => 'Cartão recusado pelo emissor. Tente outro cartão.',
        ];

        if ($statusDetail && isset($detailMessages[$statusDetail])) {
            return $detailMessages[$statusDetail];
        }

        return match ($reason) {
            'token_expired' => 'A sessão de pagamento expirou. Preencha os dados do cartão novamente.',
            'card_declined' => 'Cartão recusado. Tente outro cartão.',
            'internal_error' => 'Não foi possível processar seu pagamento agora. Tente novamente em instantes.',
            default => 'Não foi possível processar seu pagamento. Tente novamente.',
        };
    }

    private function sendFailureEmail(Order $order): void
    {
        if (empty($order->buyer_email)) {
            Log::warning('PaymentFailedMail não enviado: buyer_email ausente', [
                'order_id' => $order->id,
            ]);

            return;
        }

        try {
            Mail::to($order->buyer_email)->send(new PaymentFailedMail($order));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar PaymentFailedMail', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
