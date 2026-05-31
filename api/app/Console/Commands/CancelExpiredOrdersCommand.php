<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-expired {--minutes= : Fallback: considera expirado tudo criado há mais de N minutos (para pedidos legados sem reserved_until)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos cuja reserva (reserved_until) expirou, liberando os ingressos para venda';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = $this->option('minutes') !== null ? (int) $this->option('minutes') : null;

        $this->info('Procurando pedidos com reserva expirada...');

        // Pedidos em PENDING ou FAILED com reserva expirada — PROCESSING fica
        // fora porque o job ainda está trabalhando neles.
        $query = Order::whereIn('status', [
                OrderStatus::PENDING->value,
                OrderStatus::FAILED->value,
            ])
            ->where(function ($q) use ($minutes) {
                $q->whereNotNull('reserved_until')->where('reserved_until', '<', now());

                if ($minutes !== null && $minutes > 0) {
                    $q->orWhere(function ($q2) use ($minutes) {
                        $q2->whereNull('reserved_until')
                           ->where('created_at', '<', Carbon::now()->subMinutes($minutes));
                    });
                }
            });

        $expiredOrders = $query->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Nenhum pedido expirado encontrado.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$expiredOrders->count()} pedido(s) expirado(s).");

        $cancelledCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                $metadata = $order->metadata ?? [];
                $metadata['cancelled_reason'] = 'reservation_expired';
                $metadata['cancelled_at'] = now()->toIso8601String();

                $order->update([
                    'status' => OrderStatus::CANCELLED,
                    'metadata' => $metadata,
                ]);

                $cancelledCount++;

                $this->line("  - Pedido {$order->reference} cancelado (criado em {$order->created_at->format('d/m/Y H:i:s')})");

                Log::info('Pedido expirado cancelado', [
                    'order_id'  => $order->id,
                    'reference' => $order->reference,
                    'created_at' => $order->created_at,
                ]);
            } catch (\Exception $e) {
                $this->error("  - Erro ao cancelar pedido {$order->reference}: {$e->getMessage()}");

                Log::error('Erro ao cancelar pedido expirado', [
                    'order_id'  => $order->id,
                    'reference' => $order->reference,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $this->info("✓ {$cancelledCount} pedido(s) cancelado(s) com sucesso.");

        return Command::SUCCESS;
    }
}
