<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPayoutSetting;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    /**
     * Definir modo de pagamento (direct ou platform) - Apenas Super Admin
     */
    public function setPayoutMode(Request $request, Event $event)
    {
        $validated = $request->validate([
            'payout_mode' => 'required|in:direct,platform',
        ]);

        // Desativar configurações antigas
        EventPayoutSetting::where('event_id', $event->id)
            ->update(['active' => false]);

        if ($validated['payout_mode'] === 'platform') {
            // Modo Platform: usar credenciais da plataforma
            try {
                $platformCredentials = MercadoPagoService::getPlatformCredentials();
                
                $payoutSetting = EventPayoutSetting::create([
                    'event_id' => $event->id,
                    'method' => 'mercadopago',
                    'payout_mode' => 'platform',
                    'provider' => 'Mercado Pago',
                    'details' => $platformCredentials,
                    'active' => true,
                ]);

                // Ativar evento automaticamente quando configurado para platform
                $event->update(['status' => 'ativo']);

                return response()->json([
                    'message' => 'Modo de pagamento configurado para Platform. Evento ativado.',
                    'data' => [
                        'payout_mode' => 'platform',
                        'status' => 'ativo',
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Erro ao configurar modo platform: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Modo Direct: criar setting vazio, organizador adiciona credenciais depois
            $payoutSetting = EventPayoutSetting::create([
                'event_id' => $event->id,
                'method' => 'mercadopago',
                'payout_mode' => 'direct',
                'provider' => 'Mercado Pago',
                'details' => [],
                'active' => true,
            ]);

            return response()->json([
                'message' => 'Modo de pagamento configurado para Direct. Organizador deve adicionar credenciais.',
                'data' => [
                    'payout_mode' => 'direct',
                    'pending_credentials' => true,
                ]
            ]);
        }
    }
}