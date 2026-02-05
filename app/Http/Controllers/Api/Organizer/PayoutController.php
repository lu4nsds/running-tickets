<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\EventPayoutSetting;
use App\Models\Event;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    /**
     * Visualizar configurações de pagamento de um evento
     */
    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        
        // Verificar se user tem acesso ao evento
        if (!$user->isSuperAdmin() && !$user->canAccessOrganizer($event->organizer_id)) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar as configurações deste evento.'
            ], 403);
        }
        
        $payoutSettings = EventPayoutSetting::where('event_id', $event->id)
            ->where('active', true)
            ->first();
        
        if (!$payoutSettings) {
            return response()->json([
                'event_id' => $event->id,
                'message' => 'Nenhuma configuração de pagamento cadastrada.'
            ]);
        }
        
        return response()->json($payoutSettings);
    }

    /**
     * Atualizar configurações de pagamento
     */
    public function update(Request $request, Event $event)
    {
        $user = $request->user();
        
        // Verificar se user tem acesso ao evento
        if (!$user->isSuperAdmin() && !$user->isOrganizerAdmin($event->organizer_id)) {
            return response()->json([
                'message' => 'Apenas administradores do organizador podem editar configurações de pagamento.'
            ], 403);
        }
        
        $validated = $request->validate([
            'method' => 'required|in:pix,bank_account,gateway,mercadopago',
            'provider' => 'nullable|string|max:100',
            'details' => 'required|array',
            // Validação específica para Mercado Pago
            'details.access_token' => 'required_if:method,mercadopago|string',
            'details.public_key' => 'required_if:method,mercadopago|string',
        ]);
        
        // Desativar configurações antigas
        EventPayoutSetting::where('event_id', $event->id)
            ->update(['active' => false]);
        
        // Criar nova configuração ativa
        $payoutSettings = EventPayoutSetting::create([
            'event_id' => $event->id,
            'method' => $validated['method'],
            'provider' => $validated['provider'] ?? null,
            'details' => $validated['details'],
            'active' => true,
        ]);
        
        return response()->json([
            'message' => 'Configurações de pagamento atualizadas com sucesso.',
            'data' => $payoutSettings
        ]);
    }
}
