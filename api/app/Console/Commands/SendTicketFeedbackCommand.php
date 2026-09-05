<?php

namespace App\Console\Commands;

use App\Enums\PlatformSettingKey;
use App\Jobs\SendOrderFeedbackJob;
use App\Models\Order;
use App\Models\PlatformSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTicketFeedbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:send-feedback
                            {--hours=24 : Horas desde a validação do ingresso antes de convidar o comprador}
                            {--lookback-days=30 : Janela retroativa máxima, para não disparar em massa em histórico antigo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia ao comprador o link do formulário de feedback 24h após a validação do ingresso';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $lookbackDays = (int) $this->option('lookback-days');

        $formUrl = PlatformSetting::getValue(PlatformSettingKey::FEEDBACK_FORM_URL);

        // Sem link configurado não há o que enviar — e nada é marcado, para que
        // os pedidos continuem elegíveis quando o super admin configurar.
        if (! $formUrl) {
            $this->warn('Link do formulário de feedback não configurado. Nada a enviar.');

            return Command::SUCCESS;
        }

        $this->info("Procurando pedidos com ingressos validados há mais de {$hours}h...");

        $dispatched = 0;

        Order::query()
            ->awaitingFeedback($hours, $lookbackDays)
            ->with('event')
            ->chunkById(200, function ($orders) use ($formUrl, &$dispatched) {
                foreach ($orders as $order) {
                    // Reivindica o pedido com um UPDATE condicional antes de
                    // despachar. A fila é assíncrona: sem isso, um run seguinte
                    // encontraria o pedido ainda não marcado e enviaria de novo.
                    $claimed = Order::query()
                        ->whereKey($order->getKey())
                        ->whereNull('feedback_sent_at')
                        ->update(['feedback_sent_at' => now()]);

                    if ($claimed === 0) {
                        continue;
                    }

                    SendOrderFeedbackJob::dispatch($order, $formUrl);
                    $dispatched++;

                    $this->line("  - Feedback agendado para o pedido {$order->reference}");
                }
            });

        if ($dispatched === 0) {
            $this->info('Nenhum pedido elegível encontrado.');

            return Command::SUCCESS;
        }

        $this->info("✓ {$dispatched} convite(s) de feedback despachado(s).");

        Log::info('Convites de feedback despachados', [
            'orders' => $dispatched,
            'hours' => $hours,
            'lookback_days' => $lookbackDays,
        ]);

        return Command::SUCCESS;
    }
}
