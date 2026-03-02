<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
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
     * Display the specified ticket type.
     */
    public function show(Request $request, Event $event, TicketType $ticketType)
    {
        // Verificar se o ticket type pertence ao evento
        if ($ticketType->event_id !== $event->id) {
            return response()->json([
                'message' => 'Este tipo de ingresso não pertence a este evento.'
            ], 404);
        }

        return new TicketTypeResource($ticketType);
    }
}
