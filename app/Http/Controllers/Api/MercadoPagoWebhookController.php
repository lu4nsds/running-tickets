<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService
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

            // Mercado Pago envia o tipo de notificação
            $type = $request->input('type');
            
            // Só processamos notificações de pagamento
            if ($type !== 'payment') {
                Log::info('Tipo de notificação ignorado', ['type' => $type]);
                return response()->json(['status' => 'ignored'], 200);
            }

            // ID do pagamento no Mercado Pago
            $paymentId = $request->input('data.id');
            
            if (!$paymentId) {
                Log::warning('Payment ID não encontrado no webhook');
                return response()->json(['error' => 'Payment ID missing'], 400);
            }

            // Busca o pedido pela referência externa
            // Precisamos buscar o access_token do organizador para consultar o pagamento
            $action = $request->input('action');
            
            Log::info('Processando pagamento', [
                'payment_id' => $paymentId,
                'action' => $action,
            ]);

            // Busca todos os pedidos pendentes (não é o ideal, mas funciona)
            // Em produção, você poderia armazenar o payment_id temporariamente
            $orders = Order::where('status', OrderStatus::PENDING)
                ->with('event.payoutSetting')
                ->get();

            foreach ($orders as $order) {
                $payoutSetting = $order->event->payoutSetting;
                
                if (!$payoutSetting || !isset($payoutSetting->details['access_token'])) {
                    continue;
                }

                // Busca o pagamento no Mercado Pago
                $payment = $this->mercadoPagoService->getPayment(
                    $paymentId,
                    $payoutSetting->details['access_token']
                );

                if (!$payment) {
                    continue;
                }

                // Verifica se o external_reference bate com este pedido
                if ($payment['external_reference'] === $order->reference) {
                    Log::info('Pedido encontrado', [
                        'order_id' => $order->id,
                        'reference' => $order->reference,
                        'payment_status' => $payment['status'],
                    ]);

                    // Atualiza o pedido baseado no status do pagamento
                    $this->updateOrderStatus($order, $payment);

                    return response()->json(['status' => 'processed'], 200);
                }
            }

            Log::warning('Pedido não encontrado para o pagamento', [
                'payment_id' => $paymentId,
            ]);

            return response()->json(['status' => 'order_not_found'], 404);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook Mercado Pago', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retorna 200 para evitar que o Mercado Pago reenvie infinitamente
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Atualiza o status do pedido baseado no pagamento
     */
    private function updateOrderStatus(Order $order, array $payment): void
    {
        $status = $payment['status'];

        Log::info('Atualizando status do pedido', [
            'order_id' => $order->id,
            'current_status' => $order->status->value,
            'payment_status' => $status,
        ]);

        switch ($status) {
            case 'approved':
                $order->update([
                    'status' => OrderStatus::PAID,
                    'payment_gateway' => 'mercadopago',
                    'payment_id' => $payment['id'],
                    'metadata' => array_merge($order->metadata ?? [], [
                        'payment_method' => $payment['payment_method_id'],
                        'payment_type' => $payment['payment_type_id'],
                        'transaction_amount' => $payment['transaction_amount'],
                    ]),
                ]);
                break;

            case 'refunded':
            case 'charged_back':
                $order->update([
                    'status' => OrderStatus::REFUNDED,
                    'payment_gateway' => 'mercadopago',
                    'payment_id' => $payment['id'],
                ]);
                break;

            case 'cancelled':
                $order->update([
                    'status' => OrderStatus::CANCELLED,
                    'payment_gateway' => 'mercadopago',
                    'payment_id' => $payment['id'],
                ]);
                break;

            default:
                Log::info('Status de pagamento não requer atualização', [
                    'status' => $status,
                ]);
                break;
        }
    }
}
