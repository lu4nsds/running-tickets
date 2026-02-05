<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard geral do organizador (todos os eventos)
     */
    public function index(Request $request)
    {
        $organizerId = $request->user()->organizers()->first()->id;
        
        $cacheKey = "dashboard_organizer_{$organizerId}";
        
        return Cache::remember($cacheKey, 300, function () use ($organizerId) {
            // Buscar todos os eventos do organizador com agregações otimizadas
            $events = Event::where('organizer_id', $organizerId)
                ->withCount([
                    'orders as total_orders' => function ($query) {
                        $query->whereIn('status', ['pending', 'paid']);
                    },
                    'orders as paid_orders' => function ($query) {
                        $query->where('status', 'paid');
                    }
                ])
                ->withSum([
                    'orders as total_revenue' => function ($query) {
                        $query->where('status', 'paid');
                    }
                ], 'total')
                ->get();

            // Resumo geral
            $summary = [
                'total_events' => $events->count(),
                'active_events' => $events->where('is_active', true)->count(),
                'total_revenue' => $events->sum('total_revenue'),
                'total_paid_orders' => $events->sum('paid_orders'),
                'total_pending_orders' => $events->sum(fn($e) => $e->total_orders - $e->paid_orders),
            ];

            // Performance de vendas por evento (top 5)
            $topEvents = $events->sortByDesc('total_revenue')->take(5)->map(function ($event) {
                // Buscar capacidade total do evento
                $totalCapacity = DB::table('ticket_types')
                    ->where('event_id', $event->id)
                    ->sum('quantity');

                // Buscar tickets vendidos
                $ticketsSold = DB::table('tickets')
                    ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('order_items.event_id', $event->id)
                    ->where('orders.status', 'paid')
                    ->count();

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => $event->event_date,
                    'revenue' => $event->total_revenue ?? 0,
                    'orders' => $event->paid_orders,
                    'tickets_sold' => $ticketsSold,
                    'capacity' => $totalCapacity,
                    'occupancy_rate' => $totalCapacity > 0 ? round(($ticketsSold / $totalCapacity) * 100, 2) : 0,
                    'conversion_rate' => $event->total_orders > 0 
                        ? round(($event->paid_orders / $event->total_orders) * 100, 2) 
                        : 0,
                ];
            })->values();

            // Análise temporal (últimos 7 dias)
            $salesByDay = Order::join('events', 'orders.event_id', '=', 'events.id')
                ->where('events.organizer_id', $organizerId)
                ->where('orders.status', 'paid')
                ->where('orders.updated_at', '>=', now()->subDays(7))
                ->select(
                    DB::raw('DATE(orders.updated_at) as date'),
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('SUM(orders.total) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'summary' => $summary,
                'top_events' => $topEvents,
                'sales_trend' => $salesByDay,
            ];
        });
    }

    /**
     * Dashboard específico de um evento
     */
    public function eventDashboard(Request $request, $eventId)
    {
        // Verificar se o organizador tem acesso ao evento
        $event = Event::findOrFail($eventId);
        $organizerId = $request->user()->organizers()->first()->id;
        
        if ($event->organizer_id !== $organizerId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cacheKey = "dashboard_event_{$eventId}";
        
        return Cache::remember($cacheKey, 300, function () use ($event) {
            // Resumo do evento com agregações otimizadas
            $summary = Order::where('event_id', $event->id)
                ->selectRaw('
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = "pending" THEN total ELSE 0 END) as pending_revenue
                ')
                ->first();

            // Funil de conversão
            $conversionFunnel = [
                'total_orders' => $summary->total_orders,
                'paid_orders' => $summary->paid_orders,
                'pending_orders' => $summary->pending_orders,
                'cancelled_orders' => $summary->cancelled_orders,
                'conversion_rate' => $summary->total_orders > 0 
                    ? round(($summary->paid_orders / $summary->total_orders) * 100, 2) 
                    : 0,
                'abandonment_rate' => $summary->total_orders > 0 
                    ? round((($summary->cancelled_orders + $summary->pending_orders) / $summary->total_orders) * 100, 2) 
                    : 0,
            ];

            // Performance por tipo de ingresso
            $ticketTypes = DB::table('ticket_types')
                ->where('event_id', $event->id)
                ->leftJoin('order_items', function ($join) {
                    $join->on('ticket_types.id', '=', 'order_items.ticket_type_id')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.status', '=', 'paid');
                })
                ->select(
                    'ticket_types.id',
                    'ticket_types.name',
                    'ticket_types.price',
                    'ticket_types.quantity as total_quantity',
                    DB::raw('COALESCE(COUNT(order_items.id), 0) as sold'),
                    DB::raw('COALESCE(SUM(order_items.subtotal), 0) as revenue')
                )
                ->groupBy('ticket_types.id', 'ticket_types.name', 'ticket_types.price', 'ticket_types.quantity')
                ->get()
                ->map(function ($type) {
                    $type->available = $type->total_quantity - $type->sold;
                    $type->sold_percentage = $type->total_quantity > 0 
                        ? round(($type->sold / $type->total_quantity) * 100, 2) 
                        : 0;
                    return $type;
                });

            // Velocidade de vendas (últimos 7 dias)
            $salesVelocity = Order::where('event_id', $event->id)
                ->where('status', 'paid')
                ->where('updated_at', '>=', now()->subDays(7))
                ->select(
                    DB::raw('DATE(updated_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Projeção de vendas (se ainda houver tempo até o evento)
            $daysUntilEvent = now()->diffInDays($event->event_date, false);
            $projection = null;
            
            if ($daysUntilEvent > 0 && $summary->paid_orders > 0) {
                $daysSinceCreated = now()->diffInDays($event->created_at);
                $avgOrdersPerDay = $daysSinceCreated > 0 ? $summary->paid_orders / $daysSinceCreated : 0;
                
                $ticketTypes->each(function ($type) use (&$projection, $avgOrdersPerDay, $daysUntilEvent) {
                    if (!$projection) $projection = [];
                    
                    $avgTicketsPerDay = $type->sold / max(now()->diffInDays($type->created_at ?? now()), 1);
                    $projectedSales = min(
                        $type->sold + ($avgTicketsPerDay * $daysUntilEvent),
                        $type->total_quantity
                    );
                    
                    $projection[] = [
                        'ticket_type' => $type->name,
                        'current_sold' => $type->sold,
                        'projected_sold' => round($projectedSales),
                        'projected_revenue' => round($projectedSales * $type->price, 2),
                    ];
                });
            }

            // Demografia (usuários vs guests)
            $demographics = Order::where('event_id', $event->id)
                ->where('status', 'paid')
                ->selectRaw('
                    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as registered_users,
                    SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as guests
                ')
                ->first();

            return [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => $event->event_date,
                    'location' => $event->location,
                    'days_until_event' => max($daysUntilEvent, 0),
                ],
                'summary' => [
                    'total_revenue' => $summary->total_revenue,
                    'pending_revenue' => $summary->pending_revenue,
                    'total_orders' => $summary->total_orders,
                    'paid_orders' => $summary->paid_orders,
                    'pending_orders' => $summary->pending_orders,
                ],
                'conversion_funnel' => $conversionFunnel,
                'ticket_types' => $ticketTypes,
                'sales_velocity' => $salesVelocity,
                'demographics' => [
                    'registered_users' => $demographics->registered_users,
                    'guests' => $demographics->guests,
                    'registered_percentage' => $summary->paid_orders > 0 
                        ? round(($demographics->registered_users / $summary->paid_orders) * 100, 2) 
                        : 0,
                ],
                'projection' => $projection,
            ];
        });
    }
}
