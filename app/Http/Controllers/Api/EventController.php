<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Enums\EventStatus;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Listar eventos públicos
     */
    public function index(Request $request)
    {
        $events = Event::with([
            'categories',
            'ticketTypes' => function ($query) {
                $query->where('active', true);
            },
        ])
        ->where('status', EventStatus::ATIVO)
        ->when($request->search, fn($q, $search) => 
            $q->where(function($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                         ->orWhere('city', 'like', "%{$search}%");
            })
        )
        ->when($request->city, fn($q, $city) => 
            $q->where('city', $city)
        )
        ->when($request->state, fn($q, $state) => 
            $q->where('state', $state)
        )
        ->when($request->date_from, fn($q, $date) => 
            $q->where('date_start', '>=', $date)
        )
        ->when($request->date_to, fn($q, $date) => 
            $q->where('date_start', '<=', $date)
        )
        ->when($request->distance, fn($q, $distance) => 
            $q->whereHas('categories', fn($cq) => $cq->where('distance', $distance))
        )
        ->when($request->min_price || $request->max_price, function($q) use($request) {
            $q->whereHas('ticketTypes', function($tq) use($request) {
                $tq->where('active', true);
                if($request->min_price) {
                    $tq->where('price_cents', '>=', $request->min_price * 100);
                }
                if($request->max_price) {
                    $tq->where('price_cents', '<=', $request->max_price * 100);
                }
            });
        });

        // Ordenação
        $sortBy = $request->sort_by ?? 'date';
        switch($sortBy) {
            case 'price_asc':
                $events->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                    ->where('ticket_types.active', true)
                    ->select('events.*', \DB::raw('MIN(ticket_types.price_cents) as min_price'))
                    ->groupBy('events.id')
                    ->orderBy('min_price', 'asc');
                break;
            case 'price_desc':
                $events->join('ticket_types', 'events.id', '=', 'ticket_types.event_id')
                    ->where('ticket_types.active', true)
                    ->select('events.*', \DB::raw('MIN(ticket_types.price_cents) as min_price'))
                    ->groupBy('events.id')
                    ->orderBy('min_price', 'desc');
                break;
            case 'date':
            default:
                $events->orderBy('date_start', 'asc');
                break;
        }

        return EventResource::collection(
            $events->paginate($request->per_page ?? 15)
        );
    }

    /**
     * Exibir detalhes de um evento específico
     */
    public function show(string $slug)
    {
        $event = Event::with([
            'categories',
            'ticketTypes' => function ($query) {
                $query->where('active', true)
                    ->withCount([
                        'orderItems as sold_count' => function ($q) {
                            $q->whereHas('order', function ($oq) {
                                $oq->whereIn('status', ['pending', 'paid']);
                            });
                        }
                    ]);
            },
            'organizer'
        ])
        ->where('slug', $slug)
        ->where('status', EventStatus::ATIVO)
        ->firstOrFail();

        return new EventResource($event);
    }

    /**
     * Listar cidades distintas dos eventos ativos
     */
    public function cities()
    {
        return Event::where('status', EventStatus::ATIVO)
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->filter()
            ->values();
    }

    /**
     * Listar estados distintos dos eventos ativos
     */
    public function states()
    {
        return Event::where('status', EventStatus::ATIVO)
            ->distinct()
            ->orderBy('state')
            ->pluck('state')
            ->filter()
            ->values();
    }
}
