<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Listar eventos do organizador
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Super admin vê todos
        if ($user->isSuperAdmin()) {
            $events = Event::query();
        } else {
            // Organizer vê apenas de seus organizadores
            $organizerIds = $user->organizers()->pluck('organizers.id');
            
            if ($organizerIds->isEmpty()) {
                return response()->json([
                    'message' => 'Você não está vinculado a nenhum organizador.'
                ], 403);
            }
            
            $events = Event::whereIn('organizer_id', $organizerIds);
        }
        
        $events = $events
            ->with(['organizer'])
            ->when($request->search, fn($q, $search) => 
                $q->where('title', 'like', "%{$search}%")
            )
            ->when($request->status, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->orderBy('date_start', 'desc')
            ->paginate($request->per_page ?? 20);

        return EventResource::collection($events);
    }

    /**
     * Visualizar detalhes de um evento específico
     */
    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        
        // Verificar se user tem acesso ao evento
        if (!$user->isSuperAdmin() && !$user->canAccessOrganizer($event->organizer_id)) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar este evento.'
            ], 403);
        }
        
        $event->load(['organizer', 'categories', 'ticketTypes']);
        
        // Estatísticas de tickets (validação)
        $event->ticket_stats = $event->getTicketStatistics();
        
        return new EventResource($event);
    }
}
