<?php

namespace App\Console\Commands;

use App\Models\PaymentGatewayAccount;
use App\Services\Payment\MercadoPagoOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshMercadoPagoTokensCommand extends Command
{
    protected $signature = 'mercadopago:refresh-tokens';

    protected $description = 'Renova os access tokens OAuth do Mercado Pago que estão perto de vencer (split de pagamento)';

    public function handle(MercadoPagoOAuthService $oauth): int
    {
        $threshold = (int) config('mercadopago.oauth.refresh_threshold');
        $limit = now()->addSeconds($threshold);

        $accounts = PaymentGatewayAccount::query()
            ->where('gateway', \App\Enums\PaymentGateway::MERCADOPAGO->value)
            ->where('status', PaymentGatewayAccount::STATUS_CONNECTED)
            ->whereNotNull('refresh_token')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $limit)
            ->get();

        $this->info("Contas para renovar: {$accounts->count()}");

        foreach ($accounts as $account) {
            try {
                $tokens = $oauth->refresh($account);
                $oauth->storeAccount($account->organizer, $tokens);
                $this->line("  ✓ organizador {$account->organizer_id} renovado");
            } catch (\Throwable $e) {
                $account->update(['status' => PaymentGatewayAccount::STATUS_EXPIRED]);
                Log::error('Falha ao renovar token OAuth do Mercado Pago', [
                    'organizer_id' => $account->organizer_id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("  ✗ organizador {$account->organizer_id} marcado como expired");
            }
        }

        return self::SUCCESS;
    }
}
