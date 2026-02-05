<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos pendentes que expiraram (mais de 48 horas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando pedidos expirados...');

        // Busca pedidos com status PENDING criados há mais de 48 horas
        $expirationTime = now()->subHours(48);

        $expiredOrders = Order::where('status', OrderStatus::PENDING)
            ->where('created_at', '<', $expirationTime)
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Nenhum pedido expirado encontrado.');
            return 0;
        }

        $this->info("Encontrados {$expiredOrders->count()} pedidos expirados.");

        $bar = $this->output->createProgressBar($expiredOrders->count());
        $bar->start();

        $cancelledCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                $order->update([
                    'status' => OrderStatus::CANCELLED,
                ]);

                Log::info('Pedido expirado cancelado', [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'created_at' => $order->created_at,
                ]);

                $cancelledCount++;
            } catch (\Exception $e) {
                Log::error('Erro ao cancelar pedido expirado', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✓ {$cancelledCount} pedidos cancelados com sucesso!");

        return 0;
    }
}
