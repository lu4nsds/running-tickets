<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Lista os pedidos de um evento (super admin).
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $query = Order::query()
            ->where('event_id', $event->id)
            ->with(['event', 'organizer', 'items.ticketType', 'items.category', 'items.ticket', 'cancellations'])
            ->orderBy('created_at', 'desc');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Busca por referência do pedido, e-mail do comprador ou CPF do participante
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $digits = preg_replace('/\D/', '', $search);
            // Só busca por CPF quando o termo é numérico (sem letras), para evitar
            // que dígitos avulsos de uma referência/e-mail casem qualquer CPF.
            $isCpfSearch = $digits !== '' && ! preg_match('/[a-zA-Z]/', $search);

            $query->where(function ($q) use ($search, $digits, $isCpfSearch) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%");

                if ($isCpfSearch) {
                    $q->orWhereHas('items', function ($iq) use ($digits) {
                        $iq->whereRaw("JSON_EXTRACT(participant_data, '$.cpf') LIKE ?", ["%{$digits}%"]);
                    });
                }
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return OrderResource::collection($orders)->response();
    }
}
