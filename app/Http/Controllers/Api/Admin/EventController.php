<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $events = Event::query()
            ->with(['organizer'])
            ->when($request->search, fn($q, $search) => 
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
            )
            ->when($request->organizer_id, fn($q, $organizerId) => 
                $q->where('organizer_id', $organizerId)
            )
            ->when($request->status, fn($q, $status) => 
                $q->where('status', $status)
            )
            ->orderBy('date_start', 'desc')
            ->paginate($request->per_page ?? 20);

        return EventResource::collection($events);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();
        
        // Gerar slug se não fornecido
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        
        $event = Event::create($data);

        return new EventResource($event->load('organizer'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        $event->load(['organizer', 'categories', 'ticketTypes']);
        
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->validated();
        
        // Atualizar slug se title mudou
        if (isset($data['title']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        
        $event->update($data);

        return new EventResource($event->load('organizer'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'Evento deletado com sucesso.'
        ]);
    }
}
