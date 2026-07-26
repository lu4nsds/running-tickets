<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MercadoPagoService;
use App\Services\Payment\MercadoPagoCredentialResolver;
use App\Services\PaymentResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private PaymentResultService $paymentResultService,
        private MercadoPagoCredentialResolver $credentialResolver,
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

            if (! $this->signatureIsValid($request)) {
                Log::warning('Webhook Mercado Pago com assinatura inválida');

                return response()->json(['error' => 'invalid signature'], 401);
            }

            $type = $request->input('type');

            if ($type !== 'payment') {
                Log::info('Tipo de notificação ignorado', ['type' => $type]);

                return response()->json(['status' => 'ignored'], 200);
            }

            $paymentId = $request->input('data.id');

            if (! $paymentId) {
                Log::warning('Payment ID não encontrado no webhook');

                return response()->json(['error' => 'Payment ID missing'], 400);
            }

            Log::info('Processando pagamento', [
                'payment_id' => $paymentId,
                'action' => $request->input('action'),
            ]);

            // Tenta buscar o pedido pelo payment_id primeiro (mais eficiente)
            $order = Order::where('payment_id', $paymentId)->first();

            if ($order) {
                Log::info('Pedido encontrado por payment_id', [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                ]);

                // Pedidos em split vivem na conta do organizador — resolve o
                // token pelo pedido. Modo platform usa o token global.
                $payment = $this->mercadoPagoService->getPaymentById(
                    $paymentId,
                    $this->accessTokenForOrder($order),
                );

                if ($payment) {
                    $this->updateOrderStatus($order, $payment);

                    return response()->json(['status' => 'processed'], 200);
                }
            }

            // Fallback: busca por external_reference (token global da plataforma;
            // pedidos em split são resolvidos pelo fast path acima via payment_id).
            Log::info('Buscando pedido por external_reference (fallback)');

            $payment = $this->mercadoPagoService->getPaymentById($paymentId);

            if (! $payment) {
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

            if (! $order) {
                Log::warning('Pedido não encontrado para o pagamento', [
                    'payment_id' => $paymentId,
                    'external_reference' => $payment['external_reference'],
                ]);

                return response()->json(['status' => 'order_not_found'], 200);
            }

            Log::info('Pedido encontrado por external_reference', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'payment_status' => $payment['status'],
            ]);

            $this->updateOrderStatus($order, $payment);

            return response()->json(['status' => 'processed'], 200);

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
     * Aplica o resultado do pagamento via PaymentResultService — mesma lógica
     * usada pelo ProcessCardPaymentJob. O serviço aplica um guard forward-only,
     * tornando o reprocessamento de webhooks (at-least-once / fora de ordem)
     * idempotente: pedidos já PAID/REFUNDED não regridem de status.
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

    /**
     * Resolve o access token para consultar o pagamento de um pedido. Split →
     * token do organizador; platform → token global. Nunca aborta o webhook:
     * se a resolução falhar (ex.: organizador desconectado), cai no token global.
     */
    private function accessTokenForOrder(Order $order): ?string
    {
        try {
            return $this->credentialResolver
                ->resolveForOrder($order->loadMissing('event.organizer.paymentAccount'))
                ->accessToken;
        } catch (\Throwable $e) {
            Log::warning('Não foi possível resolver token do organizador no webhook', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Valida a assinatura x-signature do Mercado Pago quando um segredo está
     * configurado. Sem segredo (padrão), a validação é pulada e o webhook segue
     * confiando na rebusca do pagamento via API como mitigação.
     *
     * Template do MP: id:<data.id>;request-id:<x-request-id>;ts:<ts>;
     */
    private function signatureIsValid(Request $request): bool
    {
        $secret = (string) config('mercadopago.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');
        $dataId = $request->query('data.id') ?? $request->input('data.id');

        if (! $signature || ! $dataId) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $piece) {
            $kv = explode('=', trim($piece), 2);
            if (count($kv) === 2) {
                $parts[trim($kv[0])] = trim($kv[1]);
            }
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (! $ts || ! $v1) {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', strtolower((string) $dataId), $requestId, $ts);
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }
}
