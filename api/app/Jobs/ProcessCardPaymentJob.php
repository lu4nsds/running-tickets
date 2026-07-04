<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use App\Services\PaymentResultService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCardPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 3;

    /**
     * @param  array  $paymentData  ['token','payment_method_id','installments','payer','payment_method']
     */
    public function __construct(
        public Order $order,
        public array $paymentData,
    ) {
        $this->onQueue('payments');
    }

    public function handle(
        MercadoPagoService $mp,
        PaymentResultService $result,
        OrderService $orderService,
    ): void {
        $this->order->refresh();

        if (! in_array($this->order->status, [OrderStatus::PROCESSING, OrderStatus::PENDING], true)) {
            Log::info('ProcessCardPaymentJob: pedido fora de PROCESSING/PENDING, ignorando', [
                'order_id' => $this->order->id,
                'status' => $this->order->status->value,
            ]);

            return;
        }

        // Barreira final contra overselling — lock no banco.
        $orderService->assertAvailabilityLocked($this->order);

        $payer = $this->paymentData['payer'];
        $buyerPhone = isset($payer['phone']) ? preg_replace('/[^0-9+]/', '', $payer['phone']) : null;

        $this->order->update([
            'buyer_email' => $payer['email'] ?? $this->order->buyer_email,
            'buyer_phone' => $buyerPhone ?? $this->order->buyer_phone,
        ]);

        $mpResponse = $mp->createCardPayment(
            token: $this->paymentData['token'],
            amountCents: $this->order->total_cents,
            paymentMethodId: $this->paymentData['payment_method_id'],
            installments: $this->paymentData['installments'] ?? 1,
            payer: $payer,
            externalReference: $this->order->reference,
        );

        $result->apply($this->order->fresh(), $mpResponse);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCardPaymentJob falhou', [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'error' => $e->getMessage(),
        ]);

        try {
            app(PaymentResultService::class)->markFailed($this->order->fresh(), $e);
        } catch (Throwable $inner) {
            Log::error('Erro ao marcar pedido como falho', [
                'order_id' => $this->order->id,
                'error' => $inner->getMessage(),
            ]);
        }
    }
}
