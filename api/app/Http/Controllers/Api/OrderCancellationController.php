<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderCancellationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewOrderCancellationRequest;
use App\Http\Requests\StoreBatchOrderCancellationRequest;
use App\Http\Resources\OrderCancellationResource;
use App\Jobs\SendCancellationReviewedNotificationJob;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class OrderCancellationController extends Controller
{
    public function __construct(private RefundService $refundService) {}

    /**
     * Lista os cancelamentos de um evento (super admin).
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $query = OrderCancellation::query()
            ->whereHas('order', fn ($q) => $q->where('event_id', $event->id))
            ->with(['order.items.ticketType', 'order.items.category', 'order.items.ticket', 'requestedBy'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        // Busca por referência do pedido, e-mail do comprador ou CPF do participante
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $digits = preg_replace('/\D/', '', $search);
            // Só busca por CPF quando o termo é numérico (sem letras), para evitar
            // que dígitos avulsos de uma referência/e-mail casem qualquer CPF.
            $isCpfSearch = $digits !== '' && ! preg_match('/[a-zA-Z]/', $search);

            $query->whereHas('order', function ($oq) use ($search, $digits, $isCpfSearch) {
                $oq->where(function ($q) use ($search, $digits, $isCpfSearch) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('buyer_email', 'like', "%{$search}%");

                    if ($isCpfSearch) {
                        $q->orWhereHas('items', function ($iq) use ($digits) {
                            $iq->whereRaw("JSON_EXTRACT(participant_data, '$.cpf') LIKE ?", ["%{$digits}%"]);
                        });
                    }
                });
            });
        }

        $cancellations = $query->paginate($request->per_page ?? 10)->withQueryString();

        return OrderCancellationResource::collection($cancellations)->response();
    }

    /**
     * Cria solicitações de cancelamento para um ou mais pedidos pagos do
     * comprador em um único request (estorno avaliado pela organização).
     */
    public function storeBatch(StoreBatchOrderCancellationRequest $request): JsonResponse
    {
        $orders = Order::whereIn('reference', $request->validated('references'))->get();

        $cancellations = DB::transaction(function () use ($orders, $request) {
            return $orders->map(function (Order $order) use ($request) {
                $this->authorize('create', [OrderCancellation::class, $order]);

                $cancellation = OrderCancellation::create([
                    'order_id' => $order->id,
                    'requested_by' => $request->user()->id,
                    'reason' => $request->validated('reason'),
                    'status' => OrderCancellationStatus::PENDING,
                ]);

                // Desativa os ingressos enquanto a solicitação estiver pendente.
                $this->refundService->deactivateTickets($order);

                return $cancellation;
            });
        });

        return response()->json([
            'message' => 'Solicitação de cancelamento enviada com sucesso.',
            'cancellations' => OrderCancellationResource::collection($cancellations),
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

        SendCancellationReviewedNotificationJob::dispatch($result);

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

        SendCancellationReviewedNotificationJob::dispatch($result);

        return response()->json([
            'message' => 'Solicitação de cancelamento rejeitada.',
            'cancellation' => OrderCancellationResource::make($result),
        ]);
    }
}
