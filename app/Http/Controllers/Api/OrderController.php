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

            // Busca o evento
            $event = Event::with('organizer')->findOrFail($request->event_id);

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

            // Cria a preferência de pagamento no Mercado Pago
            try {
                $paymentData = $this->mercadoPagoService->createPreference($order);
                
                // Salva o preference_id no metadata
                $order->update([
                    'metadata' => [
                        'preference_id' => $paymentData['preference_id'],
                    ]
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Pedido criado com sucesso!',
                    'order' => OrderResource::make($order),
                    'payment_url' => $paymentData['init_point'],
                    'sandbox_payment_url' => $paymentData['sandbox_init_point'],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                
                return response()->json([
                    'message' => 'Erro ao gerar link de pagamento: ' . $e->getMessage(),
                ], 500);
            }

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
