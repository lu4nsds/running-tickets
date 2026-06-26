<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
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

    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();
        unset($data['banner']);

        if ($request->hasFile('banner')) {
            $data['banner_url'] = $request->file('banner')->store('events/banners');
        }

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $event = Event::create($data);

        return new EventResource($event->load('organizer'));
    }

    public function show(Event $event)
    {
        $event->load([
            'organizer',
            'categories' => function ($query) {
                $query->withCount('orderItems');
            },
            'ticketTypes' => function ($query) {
                $query->withCount('orderItems');
            },
        ]);

        $event->participants_count = $event->orders()
            ->where('status', 'paid')
            ->withCount('items')
            ->get()
            ->sum('items_count');

        $paidOrders = $event->orders()
            ->where('status', 'paid')
            ->selectRaw('SUM(total_cents) as total, SUM(COALESCE(net_amount_cents, total_cents)) as net')
            ->first();

        $event->total_revenue     = $paidOrders->total ?? 0;
        $event->total_net_revenue = $paidOrders->net   ?? 0;

        $event->ticket_stats = $event->getTicketStatistics();

        return new EventResource($event);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->validated();
        unset($data['banner']);

        if ($request->hasFile('banner')) {
            if ($event->banner_url && !str_starts_with($event->banner_url, 'http')) {
                Storage::delete($event->banner_url);
            }
            $data['banner_url'] = $request->file('banner')->store('events/banners');
        }

        if (isset($data['title']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $event->update($data);

        return new EventResource($event->load('organizer'));
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(['message' => 'Evento deletado com sucesso.']);
    }
}
