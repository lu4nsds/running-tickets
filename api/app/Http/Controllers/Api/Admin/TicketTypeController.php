<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketTypeRequest;
use App\Http\Requests\UpdateTicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    /**
     * Display a listing of ticket types for an event.
     */
    public function index(Request $request, Event $event)
    {
        $ticketTypes = TicketType::where('event_id', $event->id)
            ->orderBy('name')
            ->paginate(20);

        return TicketTypeResource::collection($ticketTypes);
    }

    /**
     * Store a newly created ticket type.
     */
    public function store(StoreTicketTypeRequest $request, Event $event)
    {
        $validated = $request->validated();
        $validated['event_id'] = $event->id;

        $ticketType = TicketType::create($validated);

        return (new TicketTypeResource($ticketType))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified ticket type.
     */
    public function show(Event $event, TicketType $ticketType)
    {
        // Verificar se o ticket type pertence ao evento
        if ($ticketType->event_id !== $event->id) {
            return response()->json([
                'message' => 'Este tipo de ingresso não pertence a este evento.'
            ], 404);
        }

        return new TicketTypeResource($ticketType);
    }

    /**
     * Update the specified ticket type.
     */
    public function update(UpdateTicketTypeRequest $request, Event $event, TicketType $ticketType)
    {
        // Verificar se o ticket type pertence ao evento
        if ($ticketType->event_id !== $event->id) {
            return response()->json([
                'message' => 'Este tipo de ingresso não pertence a este evento.'
            ], 404);
        }

        $validated = $request->validated();
        $ticketType->update($validated);

        return new TicketTypeResource($ticketType);
    }

    /**
     * Remove the specified ticket type.
     */
    public function destroy(Event $event, TicketType $ticketType)
    {
        // Verificar se o ticket type pertence ao evento
        if ($ticketType->event_id !== $event->id) {
            return response()->json([
                'message' => 'Este tipo de ingresso não pertence a este evento.'
            ], 404);
        }

        // Verificar se existem inscrições vinculadas
        $orderItemsCount = $ticketType->orderItems()->count();
        
        if ($orderItemsCount > 0) {
            return response()->json([
                'message' => "Não é possível excluir este tipo de ingresso pois existem {$orderItemsCount} inscrições vinculadas.",
                'order_items_count' => $orderItemsCount
            ], 422);
        }

        $ticketType->delete();

        return response()->json([
            'message' => 'Tipo de ingresso excluído com sucesso.'
        ], 200);
    }
}
