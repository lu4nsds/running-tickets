<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
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
                        $query->whereIn('status', [OrderStatus::PENDING->value, OrderStatus::PAID->value]);
                    },
                    'orders as paid_orders' => function ($query) {
                        $query->where('status', OrderStatus::PAID->value);
                    }
                ])
                ->withSum([
                    'orders as total_revenue' => function ($query) {
                        $query->where('status', OrderStatus::PAID->value);
                    }
                ], 'total_cents')
                ->get();

            // Resumo geral
            $summary = [
                'total_events' => $events->count(),
                'active_events' => $events->count(),
                'total_revenue' => $events->sum('total_revenue') / 100,
                'total_paid_orders' => $events->sum('paid_orders'),
                'total_pending_orders' => $events->sum(fn($e) => $e->total_orders - $e->paid_orders),
            ];

            // Performance de vendas por evento (top 5)
            $topEvents = $events->sortByDesc('total_revenue')->take(5)->map(function ($event) {
                // Buscar capacidade total do evento
                $totalCapacity = DB::table('ticket_types')
                    ->where('event_id', $event->id)
                    ->sum('quota');

                // Buscar tickets vendidos
                $ticketsSold = DB::table('tickets')
                    ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
                    ->where('ticket_types.event_id', $event->id)
                    ->where('orders.status', OrderStatus::PAID->value)
                    ->count();

                return [
                    'id' => $event->id,
                    'name' => $event->title,
                    'date' => $event->date_start,
                    'date_start' => $event->date_start,
                    'date_end' => $event->date_end,
                    'status' => $event->status->value,
                    'revenue' => ($event->total_revenue ?? 0) / 100,
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
                ->where('orders.status', OrderStatus::PAID->value)
                ->where('orders.updated_at', '>=', now()->subDays(7))
                ->select(
                    DB::raw('DATE(orders.updated_at) as date'),
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('SUM(orders.total_cents) / 100 as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Vendas por tipo de ticket
            $ticketTypesSales = DB::table('ticket_types')
                ->join('events', 'ticket_types.event_id', '=', 'events.id')
                ->join('order_items', 'ticket_types.id', '=', 'order_items.ticket_type_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('events.organizer_id', $organizerId)
                ->where('orders.status', OrderStatus::PAID->value)
                ->select(
                    'ticket_types.name',
                    DB::raw('COUNT(order_items.id) as quantity_sold'),
                    DB::raw('SUM(ticket_types.price_cents) / 100 as total_revenue')
                )
                ->groupBy('ticket_types.name')
                ->orderByDesc('quantity_sold')
                ->limit(10)
                ->get();

            return [
                'summary' => $summary,
                'top_events' => $topEvents,
                'sales_trend' => $salesByDay,
                'ticket_types_sales' => $ticketTypesSales,
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
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(CASE WHEN status = 'paid' THEN total_cents ELSE 0 END) / 100 as total_revenue,
                    SUM(CASE WHEN status = 'pending' THEN total_cents ELSE 0 END) / 100 as pending_revenue
                ")
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
                ->where('ticket_types.event_id', $event->id)
                ->leftJoin('order_items', function ($join) {
                    $join->on('ticket_types.id', '=', 'order_items.ticket_type_id')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.status', '=', OrderStatus::PAID->value);
                })
                ->select(
                    'ticket_types.id',
                    'ticket_types.name',
                    'ticket_types.price_cents',
                    'ticket_types.quota as total_quantity',
                    DB::raw('COALESCE(COUNT(order_items.id), 0) as sold'),
                    DB::raw('COALESCE(SUM(ticket_types.price_cents), 0) / 100 as revenue')
                )
                ->groupBy('ticket_types.id', 'ticket_types.name', 'ticket_types.price_cents', 'ticket_types.quota')
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
                ->where('status', OrderStatus::PAID->value)
                ->where('updated_at', '>=', now()->subDays(7))
                ->select(
                    DB::raw('DATE(updated_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total_cents) / 100 as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Projeção de vendas (se ainda houver tempo até o evento)
            $daysUntilEvent = (int) now()->diffInDays($event->date_start, false);
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
                        'projected_revenue' => round($projectedSales * ($type->price_cents / 100), 2),
                    ];
                });
            }

            // Demografia por gênero (baseado nas categorias dos tickets vendidos)
            $genderDemographics = DB::table('tickets')
                ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('categories', 'order_items.category_id', '=', 'categories.id')
                ->where('orders.event_id', $event->id)
                ->where('orders.status', OrderStatus::PAID->value)
                ->select(
                    'categories.gender',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('categories.gender')
                ->get();

            $totalTickets = $genderDemographics->sum('total');
            $demographics = [
                'male' => 0,
                'female' => 0,
                'other' => 0,
                'male_percentage' => 0,
                'female_percentage' => 0,
                'other_percentage' => 0,
            ];

            foreach ($genderDemographics as $demo) {
                $count = $demo->total;
                $percentage = $totalTickets > 0 ? round(($count / $totalTickets) * 100, 2) : 0;
                
                switch ($demo->gender) {
                    case 'M':
                        $demographics['male'] = $count;
                        $demographics['male_percentage'] = $percentage;
                        break;
                    case 'F':
                        $demographics['female'] = $count;
                        $demographics['female_percentage'] = $percentage;
                        break;
                    case 'U':
                        $demographics['other'] = $count;
                        $demographics['other_percentage'] = $percentage;
                        break;
                }
            }

            // Vendas por categoria
            $salesByCategory = DB::table('tickets')
                ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('categories', 'order_items.category_id', '=', 'categories.id')
                ->where('orders.event_id', $event->id)
                ->where('orders.status', OrderStatus::PAID->value)
                ->select(
                    'categories.name',
                    DB::raw('COUNT(*) as tickets_sold')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('tickets_sold')
                ->get();

            return [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->title,
                    'date' => $event->date_start,
                    'location' => $event->city . ' - ' . $event->venue,
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
                'demographics' => $demographics,
                'sales_by_category' => $salesByCategory,
                'projection' => $projection,
            ];
        });
    }
}
