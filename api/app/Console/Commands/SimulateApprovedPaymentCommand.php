<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PaymentResultService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SimulateApprovedPaymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Disparo manual apenas — NÃO deve ser agendado.
     *
     * @var string
     */
    protected $signature = 'payments:simulate-approval
        {reference : Referência do pedido (ex.: ORD-2026-XXXX)}
        {--force : Permite rodar em ambiente de produção}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simula manualmente um retorno aprovado do Mercado Pago para um pedido (uso em testes/sandbox)';

    /**
     * Execute the console command.
     */
    public function handle(PaymentResultService $paymentResult): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Bloqueado em produção. Use --force apenas se tiver certeza absoluta.');

            return Command::FAILURE;
        }

        $reference = $this->argument('reference');

        $order = Order::where('reference', $reference)->first();

        if (! $order) {
            $this->error("Pedido {$reference} não encontrado.");

            return Command::FAILURE;
        }

        if ($order->status === OrderStatus::PAID) {
            $this->warn("Pedido {$reference} já está como PAID. Nada a fazer.");

            return Command::SUCCESS;
        }

        if (! in_array($order->status, [OrderStatus::PROCESSING, OrderStatus::PENDING, OrderStatus::FAILED], true)) {
            $this->error("Pedido {$reference} está em {$order->status->value} e não pode ser aprovado.");

            return Command::FAILURE;
        }

        $amount = $order->total_cents / 100;

        // Resposta no mesmo formato de MercadoPagoService::createCardPayment/getPaymentById,
        // para que PaymentResultService::apply trate como uma aprovação real.
        $simulated = [
            'id' => $order->payment_id ?: 'SIMULATED-'.$order->reference,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'external_reference' => $order->reference,
            'transaction_amount' => $amount,
            'payment_method_id' => $order->payment_response_body['payment_method'] ?? 'visa',
            'payment_type_id' => 'credit_card',
            'installments' => 1,
            'transaction_details' => [
                'net_received_amount' => $amount,
                'total_paid_amount' => $amount,
            ],
        ];

        $paymentResult->apply($order, $simulated);

        Log::warning('Pagamento aprovado SIMULADO manualmente', [
            'order_id' => $order->id,
            'reference' => $order->reference,
        ]);

        $this->info("✓ Pedido {$reference} marcado como PAID (simulação). Status atual: {$order->fresh()->status->value}.");
        $this->line('  A geração de tickets foi disparada pelo OrderObserver.');

        return Command::SUCCESS;
    }
}
