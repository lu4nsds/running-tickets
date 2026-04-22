<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MercadoPago\Exceptions\MPApiException;

class OrderController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService
    ) {}

    /**
     * Lista pedidos do usuário autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::with(['event', 'organizer', 'items.ticketType', 'items.category', 'items.ticket']);

        // Super admin vê todos os pedidos
        if ($user->isSuperAdmin()) {
            // Sem filtro - vê tudo
        } 
        // Organizador vê pedidos dos seus eventos
        elseif ($user->organizers()->exists()) {
            $organizerIds = $user->organizers->pluck('id');
            $query->whereIn('organizer_id', $organizerIds);
        }
        // Usuário comum vê apenas seus próprios pedidos
        else {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return OrderResource::collection($orders)->response();
    }

    /**
     * Exibe um pedido específico
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        // Load relationships
        $order->load(['event', 'organizer', 'items.ticketType', 'items.category', 'items.ticket']);

        $user = $request->user();

        // Guest não pode acessar via API
        if (!$user) {
            return response()->json([
                'message' => 'Autenticação necessária.',
            ], 401);
        }

        // Super admin pode ver todos os pedidos
        if ($user->isSuperAdmin()) {
            return OrderResource::make($order)->response();
        }

        // Organizador pode ver pedidos dos eventos do seu organizador
        if ($user->canAccessOrganizer($order->organizer_id)) {
            return OrderResource::make($order)->response();
        }

        // Usuário autenticado só vê seus próprios pedidos
        if ($order->user_id === $user->id) {
            return OrderResource::make($order)->response();
        }

        // Sem permissão
        return response()->json([
            'message' => 'Pedido não encontrado.',
        ], 404);
    }

    /**
     * Cria um novo pedido
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Busca o evento e valida se está ativo
            $event = Event::with('organizer')->findOrFail($request->event_id);

            // Cancela pedidos PENDING antigos do mesmo CPF para este evento
            // Isso evita múltiplos pedidos pendentes quando usuário abandona pagamento e tenta novamente
            $cpfsInRequest = collect($request->items)
                ->pluck('participant_data.cpf')
                ->unique()
                ->filter();

            if ($cpfsInRequest->isNotEmpty()) {
                Order::where('event_id', $event->id)
                    ->where('status', OrderStatus::PENDING->value)
                    ->whereHas('items', function ($query) use ($cpfsInRequest) {
                        foreach ($cpfsInRequest as $cpf) {
                            $query->orWhereRaw("JSON_EXTRACT(participant_data, '$.cpf') = ?", [$cpf]);
                        }
                    })
                    ->update(['status' => OrderStatus::CANCELLED->value]);
            }
            
            if ($event->status !== \App\Enums\EventStatus::ATIVO) {
                return response()->json([
                    'message' => 'Este evento não está disponível para compra de ingressos.'
                ], 422);
            }

            // Valida se todos os tipos de ingresso estão disponíveis
            $ticketTypes = [];
            foreach ($request->items as $itemData) {
                $ticketTypeId = $itemData['ticket_type_id'];
                
                if (!isset($ticketTypes[$ticketTypeId])) {
                    $ticketType = TicketType::findOrFail($ticketTypeId);
                    $ticketTypes[$ticketTypeId] = [
                        'model' => $ticketType,
                        'count' => 0
                    ];
                }
                
                $ticketTypes[$ticketTypeId]['count']++;
            }

            // Verifica disponibilidade e quota
            foreach ($ticketTypes as $ticketTypeId => $data) {
                $ticketType = $data['model'];
                $requestedQuantity = $data['count'];

                if (!$ticketType->isAvailableForPurchase()) {
                    return response()->json([
                        'message' => "O ingresso '{$ticketType->name}' não está disponível para compra.",
                        'errors' => ['ticket_type_id' => ["Ingresso indisponível: {$ticketType->name}"]]
                    ], 422);
                }

                $availableQuantity = $ticketType->getAvailableQuantity();
                if ($availableQuantity !== null && $requestedQuantity > $availableQuantity) {
                    return response()->json([
                        'message' => "Quantidade solicitada para '{$ticketType->name}' excede o disponível.",
                        'errors' => ['ticket_type_id' => [
                            "Apenas {$availableQuantity} ingressos disponíveis para '{$ticketType->name}'"
                        ]]
                    ], 422);
                }
            }

            // Calcula o total
            $totalCents = 0;
            foreach ($request->items as $itemData) {
                $ticketType = $ticketTypes[$itemData['ticket_type_id']]['model'];
                $totalCents += $ticketType->price_cents;
            }

            // Cria o pedido
            $order = Order::create([
                'event_id' => $event->id,
                'organizer_id' => $event->organizer_id,
                'reference' => Order::generateReference(),
                'user_id' => $request->user()?->id, // Null se guest
                'total_cents' => $totalCents,
                'currency' => 'BRL',
                'status' => OrderStatus::PENDING,
            ]);

            // Cria os itens do pedido
            foreach ($request->items as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'ticket_type_id' => $itemData['ticket_type_id'],
                    'category_id' => $itemData['category_id'] ?? null,
                    'participant_data' => $itemData['participant_data'],
                ]);
            }

            // Carrega os relacionamentos para o OrderResource
            $order->load(['event', 'organizer', 'items.ticketType', 'items.category']);

            // Busca public_key do organizador para uso no Checkout Transparente (Bricks)
            try {
                $publicKey = $this->mercadoPagoService->getPublicKey($order->event->payoutSetting);
            } catch (\Exception $e) {
                DB::rollBack();
                
                \Log::error('Erro ao buscar public_key do Mercado Pago', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
                
                return response()->json([
                    'message' => 'Erro ao configurar pagamento: ' . $e->getMessage(),
                ], 500);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pedido criado com sucesso!',
                'order' => OrderResource::make($order),
                'public_key' => $publicKey,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erro ao criar pedido', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar pedido.',
            ], 500);
        }
    }

    /**
     * Processa o pagamento de um pedido (Checkout Transparente)
     */
    public function processPayment(Request $request, Order $order): JsonResponse
    {
        try {
            // Valida que o pedido está pendente
            if ($order->status !== OrderStatus::PENDING) {
                return response()->json([
                    'message' => 'Este pedido não pode receber pagamento. Status atual: ' . $order->status->value,
                ], 422);
            }

            // Valida dados conforme o método de pagamento
            $validated = $request->validate([
                'payment_method' => 'required|in:credit_card,debit_card,pix',
                'token'          => 'required_if:payment_method,credit_card,debit_card|nullable|string',
                'payment_method_id' => 'required_if:payment_method,credit_card,debit_card|nullable|string',
                'installments'   => 'nullable|integer|min:1|max:12',
                'payer'          => 'required|array',
                'payer.email'    => ['required', 'email', 'regex:/^[\x00-\x7F]+$/'],
                'payer.identification'        => 'required|array',
                'payer.identification.type'   => 'required|string',
                'payer.identification.number' => 'required|string',
            ]);

            // Busca credenciais do organizador
            $order->load('event.payoutSetting');
            $payoutSetting = $order->event->payoutSetting;

            if (!$payoutSetting || $payoutSetting->method !== 'mercadopago') {
                return response()->json([
                    'message' => 'Configuração de pagamento não encontrada.',
                ], 500);
            }

            if ($payoutSetting->payout_mode === 'platform') {
                $accessToken = config('mercadopago.platform_access_token');
            } else {
                $accessToken = $payoutSetting->details['access_token'] ?? null;
            }

            if (!$accessToken) {
                return response()->json([
                    'message' => 'Credenciais de pagamento não configuradas.',
                ], 500);
            }

            // Re-verifica disponibilidade com lock para evitar overselling
            $order->load('items');
            $ticketTypeIds = $order->items->pluck('ticket_type_id')->unique();

            DB::transaction(function () use ($order, $ticketTypeIds) {
                $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)->lockForUpdate()->get();

                foreach ($ticketTypes as $ticketType) {
                    $requested = $order->items->where('ticket_type_id', $ticketType->id)->count();
                    $available = $ticketType->getAvailableQuantity();

                    if ($available !== null && $requested > $available) {
                        throw new \RuntimeException("Ingresso '{$ticketType->name}' esgotado.");
                    }
                }
            });

            $paymentMethod = $validated['payment_method'];
            $pixData = null;

            if ($paymentMethod === 'pix') {
                $paymentData = $this->mercadoPagoService->createPixPayment(
                    amountCents: $order->total_cents,
                    payer: $validated['payer'],
                    externalReference: $order->reference,
                    accessToken: $accessToken
                );

                $pixData = $paymentData['point_of_interaction']['transaction_data'] ?? null;
            } else {
                $paymentData = $this->mercadoPagoService->createCardPayment(
                    token: $validated['token'],
                    amountCents: $order->total_cents,
                    paymentMethodId: $validated['payment_method_id'],
                    installments: $validated['installments'] ?? 1,
                    payer: $validated['payer'],
                    externalReference: $order->reference,
                    accessToken: $accessToken
                );
            }

            \Log::info('Pagamento criado no Mercado Pago', [
                'order_id' => $order->id,
                'mp_payment_id' => $paymentData['id'],
                'payment_status' => $paymentData['status'],
                'payment_method' => $paymentMethod,
            ]);

            // Atualiza o pedido com dados do pagamento
            $updateData = [
                'payment_id'      => (string) $paymentData['id'],
                'payment_gateway' => 'mercadopago',
                'buyer_email'     => $validated['payer']['email'],
            ];

            $metadata = $order->metadata ?? [];
            $metadata['payment_method']    = $paymentMethod;
            $metadata['payment_method_id'] = $paymentData['payment_method_id'] ?? $paymentMethod;
            $metadata['payment_type_id']   = $paymentData['payment_type_id'] ?? null;
            $metadata['installments']      = $paymentData['installments'] ?? 1;
            $metadata['transaction_amount'] = $paymentData['transaction_amount'];
            $updateData['metadata'] = $metadata;

            $paymentStatus = $paymentData['status'];

            if ($paymentStatus === 'approved') {
                $updateData['status'] = OrderStatus::PAID;
                $netReceived = $paymentData['transaction_details']['net_received_amount'] ?? null;
                if ($netReceived !== null && $netReceived > 0) {
                    $updateData['net_amount_cents'] = (int) round($netReceived * 100);
                    $updateData['fee_cents'] = (int) round(($paymentData['transaction_amount'] - $netReceived) * 100);
                }
            } elseif (in_array($paymentStatus, ['pending', 'in_process', 'authorized'])) {
                $updateData['status'] = OrderStatus::PENDING;
            } elseif (in_array($paymentStatus, ['rejected', 'cancelled'])) {
                // NÃO cancela automaticamente - permite que o usuário tente novamente
                // com outro método de pagamento ou cartão diferente
                $updateData['status'] = OrderStatus::PENDING;
                
                // Armazena informações do pagamento rejeitado no metadata para auditoria
                $metadata['last_payment_status'] = $paymentStatus;
                $metadata['last_payment_status_detail'] = $paymentData['status_detail'] ?? null;
                $metadata['last_payment_attempt_at'] = now()->toIso8601String();
                $updateData['metadata'] = $metadata;
            }

            $order->update($updateData);
            $order->load(['event', 'organizer', 'items.ticketType', 'items.category']);

            $response = [
                'message'              => 'Pagamento processado com sucesso!',
                'order'                => OrderResource::make($order),
                'payment_status'       => $paymentStatus,
                'payment_status_detail' => $paymentData['status_detail'] ?? null,
            ];

            if ($pixData) {
                $response['pix'] = [
                    'qr_code'        => $pixData['qr_code'] ?? null,
                    'qr_code_base64' => $pixData['qr_code_base64'] ?? null,
                    'ticket_url'     => $pixData['ticket_url'] ?? null,
                ];
            }

            return response()->json($response, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'ticket_unavailable',
            ], 422);
        } catch (MPApiException $e) {
            \Log::error('Erro MP ao processar pagamento', [
                'order_id'    => $order->id ?? null,
                'message'     => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar pagamento no Mercado Pago. Verifique os dados e tente novamente.',
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erro ao processar pagamento', [
                'order_id' => $order->id ?? null,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna apenas o status de um pedido (público, por reference)
     */
    public function status(string $reference): JsonResponse
    {
        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }

        return response()->json([
            'reference' => $order->reference,
            'status'    => $order->status->value,
        ]);
    }

    /**
     * Cancela um pedido
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Verifica autorização (mesma lógica do show)
        if (!$user->isSuperAdmin() 
            && !$user->canAccessOrganizer($order->organizer_id)
            && $order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Pedido não encontrado.',
            ], 404);
        }

        if (!$order->canCancel()) {
            return response()->json([
                'message' => 'Este pedido não pode ser cancelado.',
            ], 422);
        }

        $order->update(['status' => OrderStatus::CANCELLED]);

        return response()->json([
            'message' => 'Pedido cancelado com sucesso.',
            'order' => OrderResource::make($order->fresh()),
        ]);
    }
}
