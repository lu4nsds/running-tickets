<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MercadoPagoService;
use App\Services\PaymentResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private PaymentResultService $paymentResultService,
    ) {}

    /**
     * Recebe notificações do Mercado Pago
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('Webhook Mercado Pago recebido', [
                'payload' => $request->all(),
            ]);

            $type = $request->input('type');

            if ($type !== 'payment') {
                Log::info('Tipo de notificação ignorado', ['type' => $type]);
                return response()->json(['status' => 'ignored'], 200);
            }

            $paymentId = $request->input('data.id');

            if (!$paymentId) {
                Log::warning('Payment ID não encontrado no webhook');
                return response()->json(['error' => 'Payment ID missing'], 400);
            }

            Log::info('Processando pagamento', [
                'payment_id' => $paymentId,
                'action'     => $request->input('action'),
            ]);

            // Tenta buscar o pedido pelo payment_id primeiro (mais eficiente)
            $order = Order::where('payment_id', $paymentId)->first();

            if ($order) {
                Log::info('Pedido encontrado por payment_id', [
                    'order_id'  => $order->id,
                    'reference' => $order->reference,
                ]);

                $payment = $this->mercadoPagoService->getPaymentById($paymentId);

                if ($payment) {
                    $this->updateOrderStatus($order, $payment);
                    return response()->json(['status' => 'processed'], 200);
                }
            }

            // Fallback: busca por external_reference
            Log::info('Buscando pedido por external_reference (fallback)');

            $payment = $this->mercadoPagoService->getPaymentById($paymentId);

            if (!$payment) {
                Log::warning('Pagamento não encontrado no Mercado Pago', ['payment_id' => $paymentId]);
                return response()->json(['status' => 'payment_not_found'], 200);
            }

            $order = Order::where('reference', $payment['external_reference'])
                ->whereIn('status', [
                    OrderStatus::PENDING->value,
                    OrderStatus::PROCESSING->value,
                    OrderStatus::FAILED->value,
                ])
                ->first();

            if (!$order) {
                Log::warning('Pedido não encontrado para o pagamento', [
                    'payment_id'         => $paymentId,
                    'external_reference' => $payment['external_reference'],
                ]);
                return response()->json(['status' => 'order_not_found'], 200);
            }

            Log::info('Pedido encontrado por external_reference', [
                'order_id'       => $order->id,
                'reference'      => $order->reference,
                'payment_status' => $payment['status'],
            ]);

            $this->updateOrderStatus($order, $payment);
            return response()->json(['status' => 'processed'], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook Mercado Pago', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Retorna 200 para evitar que o Mercado Pago reenvie infinitamente
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Aplica o resultado do pagamento via PaymentResultService —
     * mesma lógica usada pelo ProcessCardPaymentJob (idempotente).
     */
    private function updateOrderStatus(Order $order, array $payment): void
    {
        Log::info('Atualizando status do pedido via webhook', [
            'order_id' => $order->id,
            'current_status' => $order->status->value,
            'payment_status' => $payment['status'] ?? null,
        ]);

        $this->paymentResultService->apply($order, $payment);
    }
}
