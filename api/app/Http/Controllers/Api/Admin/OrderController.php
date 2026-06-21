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

        $orders = $query->paginate(15);

        return OrderResource::collection($orders)->response();
    }
}
