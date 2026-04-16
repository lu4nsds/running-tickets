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
    protected $signature = 'orders:cancel-expired {--minutes=30 : Tempo em minutos para considerar um pedido como expirado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos pendentes que expiraram sem pagamento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = (int) $this->option('minutes');
        
        if ($minutes <= 0) {
            $this->error('O valor de --minutes deve ser maior que 0');
            return Command::FAILURE;
        }

        $expirationTime = Carbon::now()->subMinutes($minutes);

        $this->info("Procurando pedidos pendentes criados antes de {$expirationTime->format('d/m/Y H:i:s')}...");

        // Busca pedidos PENDING criados há mais de X minutos
        $expiredOrders = Order::where('status', OrderStatus::PENDING)
            ->where('created_at', '<', $expirationTime)
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Nenhum pedido expirado encontrado.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$expiredOrders->count()} pedido(s) expirado(s).");

        $cancelledCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                $metadata = $order->metadata ?? [];
                $metadata['cancelled_reason'] = 'expired';
                $metadata['cancelled_at'] = now()->toIso8601String();
                $metadata['expired_after_minutes'] = $minutes;

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
