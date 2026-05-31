<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\ProcessCardPaymentJob;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use App\Services\PaymentResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class OrderController extends Controller
{
    public function __construct(
        private MercadoPagoService $mercadoPagoService,
        private OrderService $orderService,
        private PaymentResultService $paymentResultService,
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
                // Envolve as condições por CPF em where() para não romper a
                // constraint de relacionamento do whereHas — orWhereRaw "solto"
                // gera `... orders.id = order_items.order_id OR (cpf=...)`,
                // fazendo o EXISTS sempre verdadeiro.
                Order::where('event_id', $event->id)
                    ->where('status', OrderStatus::PENDING->value)
                    ->whereHas('items', function ($query) use ($cpfsInRequest) {
                        $query->where(function ($q) use ($cpfsInRequest) {
                            foreach ($cpfsInRequest as $cpf) {
                                $q->orWhereRaw(
                                    "JSON_EXTRACT(participant_data, '$.cpf') = ?",
                                    [$cpf]
                                );
                            }
                        });
                    })
                    ->update(['status' => OrderStatus::CANCELLED->value]);
            }
            
            if ($event->status !== EventStatus::ATIVO) {
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

            // Cria o pedido com reserva de estoque (TTL)
            $order = Order::create([
                'event_id' => $event->id,
                'organizer_id' => $event->organizer_id,
                'reference' => Order::generateReference(),
                'user_id' => $request->user()?->id,
                'total_cents' => $totalCents,
                'currency' => 'BRL',
                'status' => OrderStatus::PENDING,
                'reserved_until' => $this->orderService->reservationExpiry(),
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

            DB::commit();

            return response()->json([
                'message' => 'Pedido criado com sucesso!',
                'order' => OrderResource::make($order),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao criar pedido', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar pedido.',
            ], 500);
        }
    }

    /**
     * Processa o pagamento de um pedido (Checkout Transparente).
     *
     * PIX → síncrono (chamada rápida ao MP, retorna QR e persiste em payment_response_body).
     * Cartão → enfileira ProcessCardPaymentJob na fila "payments" e retorna 202.
     */
    public function processPayment(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        // Pedido precisa estar em estado pagável (PENDING ou FAILED) com reserva ativa.
        if (!$order->status->isPayable()) {
            return response()->json([
                'message' => 'Este pedido não pode receber pagamento. Status atual: ' . $order->status->value,
                'error_code' => 'invalid_status',
            ], 422);
        }

        if (!$order->hasActiveReservation()) {
            return response()->json([
                'message' => 'A reserva dos ingressos expirou. Inicie um novo pedido.',
                'error_code' => 'reservation_expired',
            ], 422);
        }

        $validated = $request->validated();
        $paymentMethod = $validated['payment_method'];

        try {
            $this->orderService->assertAvailability($order);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'ticket_unavailable',
            ], 422);
        }

        // PIX continua síncrono — chamada rápida ao MP.
        if ($paymentMethod === 'pix') {
            return $this->processPixSync($order, $validated);
        }

        // Cartão: dispatch para a fila "payments" e responde imediatamente.
        return $this->dispatchCardPayment($order, $validated);
    }

    /**
     * PIX síncrono — reaproveita QR já criado quando disponível para evitar
     * duplicar pagamento se o usuário recarregar a tela.
     */
    private function processPixSync(Order $order, array $validated): JsonResponse
    {
        $pending = $order->getPendingPixData();
        if ($pending) {
            $order->refresh()->load(['event', 'organizer', 'items.ticketType', 'items.category']);

            return response()->json([
                'message' => 'PIX pendente reaproveitado.',
                'order' => OrderResource::make($order),
                'payment_status' => 'pending',
                'pix' => [
                    'qr_code' => $pending['qr_code'] ?? null,
                    'qr_code_base64' => $pending['qr_code_base64'] ?? null,
                    'ticket_url' => $pending['ticket_url'] ?? null,
                ],
            ], 200);
        }

        try {
            $mpResponse = $this->mercadoPagoService->createPixPayment(
                amountCents: $order->total_cents,
                payer: $validated['payer'],
                externalReference: $order->reference,
            );
        } catch (MPApiException $e) {
            Log::error('Erro MP ao criar PIX', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

            return response()->json([
                'message' => 'Erro ao gerar pagamento PIX. Tente novamente.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Erro inesperado ao criar PIX', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Erro ao gerar pagamento PIX.'], 500);
        }

        $order->update([
            'buyer_email' => $validated['payer']['email'],
            'buyer_phone' => preg_replace('/[^0-9+]/', '', $validated['payer']['phone']),
        ]);

        $this->paymentResultService->applyPixCreation($order, $mpResponse);

        $order->refresh()->load(['event', 'organizer', 'items.ticketType', 'items.category']);
        $pix = $order->payment_response_body ?? [];

        return response()->json([
            'message' => 'PIX gerado com sucesso.',
            'order' => OrderResource::make($order),
            'payment_status' => 'pending',
            'pix' => [
                'qr_code' => $pix['qr_code'] ?? null,
                'qr_code_base64' => $pix['qr_code_base64'] ?? null,
                'ticket_url' => $pix['ticket_url'] ?? null,
            ],
        ], 200);
    }

    /**
     * Cartão → dispatch para fila e responde 202. O usuário recebe o resultado
     * por e-mail (OrderPaidMail ou PaymentFailedMail).
     */
    private function dispatchCardPayment(Order $order, array $validated): JsonResponse
    {
        $payer = $validated['payer'];

        $order->update([
            'status' => OrderStatus::PROCESSING,
            'buyer_email' => $payer['email'],
            'buyer_phone' => preg_replace('/[^0-9+]/', '', $payer['phone']),
        ]);

        ProcessCardPaymentJob::dispatch($order->fresh(), [
            'payment_method' => $validated['payment_method'],
            'token' => $validated['token'],
            'payment_method_id' => $validated['payment_method_id'],
            'installments' => $validated['installments'] ?? 1,
            'payer' => $payer,
        ]);

        $order->load(['event', 'organizer', 'items.ticketType', 'items.category']);

        return response()->json([
            'message' => 'Pagamento em processamento. Você receberá uma confirmação por e-mail.',
            'order' => OrderResource::make($order),
            'payment_status' => 'processing',
        ], 202);
    }

    /**
     * Retorna o status público de um pedido (usado para polling de PIX e
     * pela tela de pagamento para hidratar Order + QR persistido).
     */
    public function status(string $reference): JsonResponse
    {
        $order = Order::with(['event', 'organizer', 'items.ticketType', 'items.category'])
            ->where('reference', $reference)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }

        $body = $order->payment_response_body ?? [];

        return response()->json([
            'reference' => $order->reference,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'outcome' => $body['outcome'] ?? null,
            'failure_reason' => $body['failure_reason'] ?? null,
            'failure_message_pt' => $body['failure_message_pt'] ?? null,
            'reserved_until' => $order->reserved_until?->toIso8601String(),
            'is_payable' => $order->isPayable(),
            'pix' => $order->getPendingPixData() ? [
                'qr_code' => $body['qr_code'] ?? null,
                'qr_code_base64' => $body['qr_code_base64'] ?? null,
                'ticket_url' => $body['ticket_url'] ?? null,
            ] : null,
            'order' => OrderResource::make($order),
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
