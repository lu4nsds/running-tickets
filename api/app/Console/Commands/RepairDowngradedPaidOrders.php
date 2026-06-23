<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairDowngradedPaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:repair-downgraded {--dry-run : Apenas lista os pedidos afetados, sem alterar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura para PAID pedidos aprovados que foram rebaixados por webhooks reenviados/fora de ordem (ingressos gerados, mas status != paid/refunded/cancelled)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Pedidos que TÊM ingressos gerados (só acontece após aprovação) mas
        // cujo status foi rebaixado, e cujo payment_response_body confirma a
        // aprovação no gateway — sinal seguro de downgrade indevido.
        $orders = Order::query()
            ->whereNotIn('status', [
                OrderStatus::PAID->value,
                OrderStatus::REFUNDED->value,
                OrderStatus::CANCELLED->value,
            ])
            ->whereHas('items.ticket')
            ->where('payment_response_body->outcome', 'approved')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Nenhum pedido rebaixado encontrado.');

            return self::SUCCESS;
        }

        $this->warn(($dryRun ? '[DRY-RUN] ' : '')."Encontrados {$orders->count()} pedido(s) para reparo:");

        foreach ($orders as $order) {
            $paidAt = $this->resolvePaidAt($order);

            $this->line(sprintf(
                '  %s | status atual: %s | paid_at: %s',
                $order->reference,
                $order->status->value,
                $paidAt->toIso8601String(),
            ));

            if ($dryRun) {
                continue;
            }

            // updateQuietly: os ingressos já existem; não queremos re-disparar
            // GenerateOrderTicketsJob nem reenviar o e-mail de confirmação via
            // OrderObserver ao restaurar o status.
            $order->updateQuietly([
                'status' => OrderStatus::PAID,
                'paid_at' => $order->paid_at ?? $paidAt,
            ]);

            Log::info('Pedido rebaixado restaurado para PAID', [
                'order_id' => $order->id,
                'reference' => $order->reference,
            ]);
        }

        if ($dryRun) {
            $this->info('Nenhuma alteração aplicada (dry-run).');
        } else {
            $this->info("{$orders->count()} pedido(s) restaurado(s) para PAID.");
        }

        return self::SUCCESS;
    }

    /**
     * Resolve o melhor marco de pagamento disponível: processed_at do gateway
     * quando presente, senão o updated_at do pedido.
     */
    private function resolvePaidAt(Order $order): \Illuminate\Support\Carbon
    {
        $processedAt = $order->payment_response_body['processed_at'] ?? null;

        if ($processedAt) {
            try {
                return \Illuminate\Support\Carbon::parse($processedAt);
            } catch (\Throwable) {
                // formato inesperado — cai no fallback
            }
        }

        return $order->updated_at;
    }
}
