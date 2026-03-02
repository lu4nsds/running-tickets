<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\EventPayoutSetting;
use App\Models\Event;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    /**
     * Listar todos os eventos com status de configuração de pagamento
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Buscar organizador do usuário
        $organizer = $user->organizers()->first();
        
        if (!$organizer) {
            return response()->json([
                'message' => 'Você não está vinculado a nenhum organizador.'
            ], 403);
        }
        
        // Buscar todos os eventos do organizador
        $events = Event::where('organizer_id', $organizer->id)
            ->with(['payoutSettings' => function ($query) {
                $query->where('active', true);
            }])
            ->orderBy('date_start', 'desc')
            ->get()
            ->map(function ($event) {
                $payout = $event->payoutSettings->first();
                
                // Considera configurado se: platform mode OU direct mode com credenciais
                $hasPayoutConfig = $payout !== null && (
                    $payout->payout_mode === 'platform' || 
                    ($payout->payout_mode === 'direct' && isset($payout->details['access_token']) && !empty($payout->details['access_token']))
                );
                
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'date_start' => $event->date_start,
                    'date_end' => $event->date_end,
                    'status' => $event->status,
                    'has_payout_config' => $hasPayoutConfig,
                    'payout_summary' => $payout ? [
                        'method' => $payout->method,
                        'provider' => $payout->provider,
                        'payout_mode' => $payout->payout_mode,
                    ] : null,
                ];
            });
        
        return response()->json([
            'events' => $events,
            'summary' => [
                'total_events' => $events->count(),
                'configured' => $events->where('has_payout_config', true)->count(),
                'pending' => $events->where('has_payout_config', false)->count(),
            ]
        ]);
    }

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
            ], 404);
        }
        
        // Se for platform mode, não expor credenciais da plataforma
        if ($payoutSettings->payout_mode === 'platform') {
            return response()->json([
                'event_id' => $event->id,
                'payout_mode' => 'platform',
                'method' => 'mercadopago',
                'provider' => 'Mercado Pago',
                'message' => 'Recebimento gerenciado pela plataforma. Pagamentos serão repassados conforme acordo.',
            ]);
        }
        
        // Se for direct mode, retornar dados mascarados
        $response = $payoutSettings->toArray();
        if (isset($response['details']['access_token'])) {
            // Mascarar access_token (mostrar apenas últimos 4 caracteres)
            $response['details']['access_token_masked'] = 'xxxx' . substr($response['details']['access_token'], -4);
            unset($response['details']['access_token']);
        }
        
        return response()->json($response);
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
        
        // Carregar configuração atual
        $currentSetting = EventPayoutSetting::where('event_id', $event->id)
            ->where('active', true)
            ->first();
        
        if (!$currentSetting) {
            return response()->json([
                'message' => 'Configure o modo de pagamento no admin primeiro.'
            ], 404);
        }
        
        // Organizador NÃO pode alterar payout_mode de platform
        if ($currentSetting->payout_mode === 'platform') {
            return response()->json([
                'message' => 'Configuração gerenciada pela plataforma. Entre em contato com o suporte para alterações.'
            ], 403);
        }
        
        // Validação de credenciais para direct mode
        $validated = $request->validate([
            'details' => 'required|array',
            'details.access_token' => 'required|string',
            'details.public_key' => 'required|string',
        ]);
        
        // Validar credenciais com API do Mercado Pago
        $service = new \App\Services\MercadoPagoService();
        $validation = $service->validateCredentials($validated['details']['access_token']);
        
        if (!$validation['valid']) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
                'error' => $validation['error']
            ], 422);
        }
        
        // Desativar configurações antigas
        EventPayoutSetting::where('event_id', $event->id)
            ->update(['active' => false]);
        
        // Criar nova configuração ativa
        $payoutSettings = EventPayoutSetting::create([
            'event_id' => $event->id,
            'method' => 'mercadopago',
            'payout_mode' => 'direct',
            'provider' => 'Mercado Pago',
            'details' => $validated['details'],
            'active' => true,
        ]);
        
        // Ativar evento automaticamente após configuração válida
        $event->update(['status' => 'ativo']);
        
        return response()->json([
            'message' => 'Configurações de pagamento atualizadas com sucesso. Evento ativado!',
            'data' => $payoutSettings,
            'account_info' => $validation['account_info'],
            'event_status' => 'ativo',
        ]);
    }

    /**
     * Validar credenciais do Mercado Pago
     */
    public function validateCredentials(Request $request, Event $event)
    {
        $user = $request->user();
        
        // Verificar se user tem acesso ao evento
        if (!$user->isSuperAdmin() && !$user->canAccessOrganizer($event->organizer_id)) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este evento.'
            ], 403);
        }
        
        $validated = $request->validate([
            'access_token' => 'required|string',
        ]);
        
        $service = new \App\Services\MercadoPagoService();
        $result = $service->validateCredentials($validated['access_token']);
        
        return response()->json($result);
    }
}
