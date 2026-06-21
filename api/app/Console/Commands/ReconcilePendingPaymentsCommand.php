<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MercadoPagoService;
use App\Services\PaymentResultService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePendingPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile-pending {--minutes=30 : Idade máxima (em minutos, via updated_at) dos pedidos a reconciliar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta o Mercado Pago para pedidos presos em PROCESSING e finaliza o status (paid/failed), sem depender do webhook';

    /**
     * Execute the console command.
     */
    public function handle(MercadoPagoService $mercadoPago, PaymentResultService $paymentResult): int
    {
        $minutes = (int) $this->option('minutes');

        $this->info('Procurando pedidos de cartão presos em PROCESSING...');

        // Pedidos de cartão em PROCESSING com payment_id, atualizados dentro da
        // janela. apply() é idempotente, então não há risco em reprocessar um
        // pedido já finalizado por uma corrida com o webhook/job.
        $orders = Order::where('status', OrderStatus::PROCESSING->value)
            ->whereNotNull('payment_id')
            ->where('updated_at', '>=', Carbon::now()->subMinutes($minutes))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Nenhum pedido pendente para reconciliar.');

            return Command::SUCCESS;
        }

        $this->info("Encontrados {$orders->count()} pedido(s) para reconciliar.");

        $reconciled = 0;

        foreach ($orders as $order) {
            try {
                $payment = $mercadoPago->getPaymentById($order->payment_id);

                if (! $payment) {
                    $this->warn("  - Pagamento não encontrado no MP para {$order->reference} (payment_id {$order->payment_id}).");

                    continue;
                }

                $paymentResult->apply($order->fresh(), $payment);

                $newStatus = $order->fresh()->status->value;
                $reconciled++;

                $this->line("  - Pedido {$order->reference}: mp_status={$payment['status']} → {$newStatus}");

                Log::info('Pagamento reconciliado', [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'payment_id' => $order->payment_id,
                    'mp_status' => $payment['status'] ?? null,
                    'new_status' => $newStatus,
                ]);
            } catch (\Throwable $e) {
                $this->error("  - Erro ao reconciliar pedido {$order->reference}: {$e->getMessage()}");

                Log::error('Erro ao reconciliar pagamento pendente', [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'payment_id' => $order->payment_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("✓ {$reconciled} pedido(s) reconciliado(s).");

        return Command::SUCCESS;
    }
}
