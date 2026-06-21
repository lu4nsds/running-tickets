<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderCancellationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewOrderCancellationRequest;
use App\Http\Requests\StoreOrderCancellationRequest;
use App\Http\Resources\OrderCancellationResource;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class OrderCancellationController extends Controller
{
    public function __construct(private RefundService $refundService) {}

    /**
     * Lista os cancelamentos de um evento (super admin).
     */
    public function index(Event $event): JsonResponse
    {
        $cancellations = OrderCancellation::query()
            ->whereHas('order', fn ($q) => $q->where('event_id', $event->id))
            ->with(['order.items.ticketType', 'requestedBy'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return OrderCancellationResource::collection($cancellations)->response();
    }

    /**
     * Cria um pedido de cancelamento para um pedido pago (comprador).
     */
    public function store(StoreOrderCancellationRequest $request, Order $order): JsonResponse
    {
        $this->authorize('create', [OrderCancellation::class, $order]);

        $cancellation = OrderCancellation::create([
            'order_id' => $order->id,
            'requested_by' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'status' => OrderCancellationStatus::PENDING,
        ]);

        return response()->json([
            'message' => 'Solicitação de cancelamento enviada com sucesso.',
            'cancellation' => OrderCancellationResource::make($cancellation),
        ], 201);
    }

    /**
     * Aprova o cancelamento e executa o estorno no gateway (super admin).
     */
    public function approve(ReviewOrderCancellationRequest $request, OrderCancellation $orderCancellation): JsonResponse
    {
        if ($orderCancellation->status->value !== 'pending') {
            return response()->json([
                'message' => 'Este cancelamento já foi avaliado.',
            ], 422);
        }

        try {
            $result = $this->refundService->approve($orderCancellation, $request->user());
        } catch (MPApiException $e) {
            Log::error('Falha ao estornar pedido no Mercado Pago', [
                'cancellation_id' => $orderCancellation->id,
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

            return response()->json([
                'message' => 'Não foi possível processar o estorno no Mercado Pago. Tente novamente.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Erro inesperado ao aprovar estorno', [
                'cancellation_id' => $orderCancellation->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar o estorno.',
            ], 500);
        }

        return response()->json([
            'message' => 'Cancelamento aprovado e estorno realizado com sucesso.',
            'cancellation' => OrderCancellationResource::make($result),
        ]);
    }

    /**
     * Rejeita o cancelamento sem tocar no gateway (super admin).
     */
    public function reject(ReviewOrderCancellationRequest $request, OrderCancellation $orderCancellation): JsonResponse
    {
        if ($orderCancellation->status->value !== 'pending') {
            return response()->json([
                'message' => 'Este cancelamento já foi avaliado.',
            ], 422);
        }

        $result = $this->refundService->reject(
            $orderCancellation,
            $request->user(),
            $request->validated('review_notes'),
        );

        return response()->json([
            'message' => 'Solicitação de cancelamento rejeitada.',
            'cancellation' => OrderCancellationResource::make($result),
        ]);
    }
}
