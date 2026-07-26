<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentGatewayAccountResource;
use App\Models\Organizer;
use App\Models\PaymentGatewayAccount;
use App\Services\Payment\MercadoPagoOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Conexão OAuth do organizador com o Mercado Pago (habilita o split).
 * Apenas o organizador Admin gerencia (Policy). O callback é público — vem do
 * navegador redirecionado pelo MP, sem o bearer token — e identifica o
 * organizador pelo `state` guardado no cache.
 */
class OrganizerPaymentAccountController extends Controller
{
    private const STATE_CACHE_PREFIX = 'mp_oauth_state:';

    private const STATE_TTL_MINUTES = 10;

    public function __construct(
        private readonly MercadoPagoOAuthService $oauth,
    ) {}

    /**
     * Status da conexão do organizador do usuário autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        $organizer = $this->resolveOrganizer($request);
        $this->authorize('manage', [PaymentGatewayAccount::class, $organizer]);

        $account = $organizer->paymentAccount;

        return response()->json([
            'account' => $account
                ? new PaymentGatewayAccountResource($account)
                : null,
        ]);
    }

    /**
     * Status da conexão de um organizador específico — usado pelo super admin
     * no formulário de evento para habilitar/desabilitar o Split.
     */
    public function adminShow(Organizer $organizer): JsonResponse
    {
        $this->authorize('manage', [PaymentGatewayAccount::class, $organizer]);

        $account = $organizer->paymentAccount;

        return response()->json([
            'account' => $account
                ? new PaymentGatewayAccountResource($account)
                : null,
        ]);
    }

    /**
     * Inicia a conexão: retorna a URL de autorização do Mercado Pago.
     */
    public function connect(Request $request): JsonResponse
    {
        $organizer = $this->resolveOrganizer($request);
        $this->authorize('manage', [PaymentGatewayAccount::class, $organizer]);

        $state = Str::random(40);
        Cache::put(
            self::STATE_CACHE_PREFIX.$state,
            $organizer->id,
            now()->addMinutes(self::STATE_TTL_MINUTES)
        );

        return response()->json([
            'authorization_url' => $this->oauth->authorizationUrl($state),
        ]);
    }

    /**
     * Callback público do OAuth. Valida o state, troca o code por tokens,
     * persiste a conexão e redireciona de volta ao admin.
     */
    public function callback(Request $request): RedirectResponse
    {
        $adminUrl = rtrim(config('mercadopago.admin_url'), '/');
        $target = $adminUrl.'/organizer/payment-settings';

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        $organizerId = $state !== ''
            ? Cache::pull(self::STATE_CACHE_PREFIX.$state)
            : null;

        if (! $organizerId || $code === '' || $request->query('error')) {
            return redirect()->away($target.'?connected=0');
        }

        $organizer = Organizer::find($organizerId);
        if (! $organizer) {
            return redirect()->away($target.'?connected=0');
        }

        try {
            $tokens = $this->oauth->exchangeCode($code);
            $this->oauth->storeAccount($organizer, $tokens);
        } catch (\Throwable $e) {
            Log::error('Erro ao concluir OAuth do Mercado Pago', [
                'organizer_id' => $organizer->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->away($target.'?connected=0');
        }

        return redirect()->away($target.'?connected=1');
    }

    /**
     * Desconecta a conta do organizador.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $organizer = $this->resolveOrganizer($request);
        $this->authorize('manage', [PaymentGatewayAccount::class, $organizer]);

        $organizer->paymentAccount?->delete();

        return response()->json([
            'message' => 'Conta do Mercado Pago desconectada.',
        ]);
    }

    /**
     * Resolve o organizador do usuário autenticado (convenção do backoffice:
     * o primeiro organizador vinculado — super admin não usa esta tela).
     */
    private function resolveOrganizer(Request $request): Organizer
    {
        $organizer = $request->user()->organizers()->first();

        abort_if($organizer === null, 403, 'Você não está vinculado a nenhum organizador.');

        return $organizer;
    }
}
