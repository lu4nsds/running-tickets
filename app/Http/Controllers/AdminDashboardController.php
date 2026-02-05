<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Dashboard geral da plataforma (visão admin)
     */
    public function index()
    {
        $cacheKey = 'dashboard_admin_platform';
        
        return Cache::remember($cacheKey, 300, function () {
            // Resumo da plataforma
            $platformSummary = Order::selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = "pending" THEN total ELSE 0 END) as pending_revenue
            ')->first();

            // Estatísticas de eventos e organizadores
            $eventStats = [
                'total_organizers' => Organizer::count(),
                'total_events' => Event::count(),
                'active_events' => Event::where('is_active', true)->count(),
                'events_last_30_days' => Event::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            // Ranking de organizadores por faturamento (top 10)
            $topOrganizers = DB::table('organizers')
                ->leftJoin('events', 'organizers.id', '=', 'events.organizer_id')
                ->leftJoin('orders', function ($join) {
                    $join->on('events.id', '=', 'orders.event_id')
                        ->where('orders.status', '=', 'paid');
                })
                ->select(
                    'organizers.id',
                    'organizers.name',
                    DB::raw('COUNT(DISTINCT events.id) as total_events'),
                    DB::raw('COUNT(orders.id) as total_orders'),
                    DB::raw('COALESCE(SUM(orders.total), 0) as total_revenue')
                )
                ->groupBy('organizers.id', 'organizers.name')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            // Breakdown financeiro por modo de pagamento
            $payoutBreakdown = DB::table('orders')
                ->join('events', 'orders.event_id', '=', 'events.id')
                ->join('event_payout_settings', function ($join) {
                    $join->on('events.id', '=', 'event_payout_settings.event_id')
                        ->where('event_payout_settings.active', '=', true);
                })
                ->where('orders.status', 'paid')
                ->select(
                    'event_payout_settings.payout_mode',
                    'event_payout_settings.method as gateway',
                    DB::raw('COUNT(orders.id) as orders_count'),
                    DB::raw('SUM(orders.total) as revenue')
                )
                ->groupBy('event_payout_settings.payout_mode', 'event_payout_settings.method')
                ->get()
                ->map(function ($row) {
                    return [
                        'payout_mode' => $row->payout_mode,
                        'gateway' => $row->gateway,
                        'orders' => $row->orders_count,
                        'revenue' => $row->revenue,
                        'mode_label' => $row->payout_mode === 'direct' 
                            ? 'Conta do organizador' 
                            : 'Conta da plataforma (repasse)',
                    ];
                });

            // Repasses pendentes (apenas para payout_mode = 'platform')
            $pendingPayouts = DB::table('orders')
                ->join('events', 'orders.event_id', '=', 'events.id')
                ->join('event_payout_settings', function ($join) {
                    $join->on('events.id', '=', 'event_payout_settings.event_id')
                        ->where('event_payout_settings.active', '=', true)
                        ->where('event_payout_settings.payout_mode', '=', 'platform');
                })
                ->join('organizers', 'events.organizer_id', '=', 'organizers.id')
                ->where('orders.status', 'paid')
                ->select(
                    'organizers.id as organizer_id',
                    'organizers.name as organizer_name',
                    DB::raw('COUNT(orders.id) as orders_count'),
                    DB::raw('SUM(orders.total) as amount_to_transfer')
                )
                ->groupBy('organizers.id', 'organizers.name')
                ->orderByDesc('amount_to_transfer')
                ->get();

            // Saúde da plataforma (últimos 30 dias)
            $platformHealth = Order::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders
                ')
                ->first();

            $healthMetrics = [
                'conversion_rate_30d' => $platformHealth->total_orders > 0 
                    ? round(($platformHealth->paid_orders / $platformHealth->total_orders) * 100, 2) 
                    : 0,
                'cancellation_rate_30d' => $platformHealth->total_orders > 0 
                    ? round(($platformHealth->cancelled_orders / $platformHealth->total_orders) * 100, 2) 
                    : 0,
                'avg_order_value' => $platformSummary->paid_orders > 0 
                    ? round($platformSummary->total_revenue / $platformSummary->paid_orders, 2) 
                    : 0,
            ];

            // Tendência de vendas (últimos 30 dias)
            $salesTrend = Order::where('status', 'paid')
                ->where('updated_at', '>=', now()->subDays(30))
                ->select(
                    DB::raw('DATE(updated_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Alertas (eventos próximos com baixo desempenho de vendas)
            $alerts = Event::where('event_date', '>=', now())
                ->where('event_date', '<=', now()->addDays(30))
                ->withCount([
                    'orders as paid_orders' => function ($query) {
                        $query->where('status', 'paid');
                    }
                ])
                ->get()
                ->filter(function ($event) {
                    // Calcular capacidade total
                    $totalCapacity = DB::table('ticket_types')
                        ->where('event_id', $event->id)
                        ->sum('quantity');

                    // Alertar se vendeu menos de 30% da capacidade com menos de 30 dias para o evento
                    $ticketsSold = DB::table('tickets')
                        ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('order_items.event_id', $event->id)
                        ->where('orders.status', 'paid')
                        ->count();

                    $occupancyRate = $totalCapacity > 0 ? ($ticketsSold / $totalCapacity) * 100 : 0;
                    
                    return $occupancyRate < 30;
                })
                ->map(function ($event) {
                    $totalCapacity = DB::table('ticket_types')
                        ->where('event_id', $event->id)
                        ->sum('quantity');

                    $ticketsSold = DB::table('tickets')
                        ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('order_items.event_id', $event->id)
                        ->where('orders.status', 'paid')
                        ->count();

                    return [
                        'event_id' => $event->id,
                        'event_name' => $event->name,
                        'event_date' => $event->event_date,
                        'days_until_event' => now()->diffInDays($event->event_date, false),
                        'tickets_sold' => $ticketsSold,
                        'total_capacity' => $totalCapacity,
                        'occupancy_rate' => round(($ticketsSold / $totalCapacity) * 100, 2),
                        'severity' => $ticketsSold < ($totalCapacity * 0.1) ? 'high' : 'medium',
                    ];
                })
                ->values();

            return [
                'summary' => array_merge((array) $platformSummary, $eventStats),
                'top_organizers' => $topOrganizers,
                'payout_breakdown' => $payoutBreakdown,
                'pending_payouts' => $pendingPayouts,
                'platform_health' => $healthMetrics,
                'sales_trend' => $salesTrend,
                'alerts' => $alerts,
            ];
        });
    }

    /**
     * Dashboard específico de um organizador (visão admin)
     */
    public function organizerDashboard($organizerId)
    {
        $organizer = Organizer::findOrFail($organizerId);
        
        $cacheKey = "dashboard_admin_organizer_{$organizerId}";
        
        return Cache::remember($cacheKey, 300, function () use ($organizer) {
            // Resumo do organizador
            $summary = DB::table('events')
                ->where('organizer_id', $organizer->id)
                ->leftJoin('orders', function ($join) {
                    $join->on('events.id', '=', 'orders.event_id')
                        ->where('orders.status', '=', 'paid');
                })
                ->selectRaw('
                    COUNT(DISTINCT events.id) as total_events,
                    COUNT(orders.id) as total_orders,
                    COALESCE(SUM(orders.total), 0) as total_revenue
                ')
                ->first();

            // Eventos do organizador
            $events = Event::where('organizer_id', $organizer->id)
                ->withCount([
                    'orders as paid_orders' => function ($query) {
                        $query->where('status', 'paid');
                    }
                ])
                ->withSum([
                    'orders as revenue' => function ($query) {
                        $query->where('status', 'paid');
                    }
                ], 'total')
                ->get()
                ->map(function ($event) {
                    $totalCapacity = DB::table('ticket_types')
                        ->where('event_id', $event->id)
                        ->sum('quantity');

                    $ticketsSold = DB::table('tickets')
                        ->join('order_items', 'tickets.order_item_id', '=', 'order_items.id')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('order_items.event_id', $event->id)
                        ->where('orders.status', 'paid')
                        ->count();

                    // Buscar configuração de payout
                    $payoutSetting = DB::table('event_payout_settings')
                        ->where('event_id', $event->id)
                        ->where('active', true)
                        ->first();

                    return [
                        'id' => $event->id,
                        'name' => $event->name,
                        'date' => $event->event_date,
                        'revenue' => $event->revenue ?? 0,
                        'orders' => $event->paid_orders,
                        'tickets_sold' => $ticketsSold,
                        'capacity' => $totalCapacity,
                        'occupancy_rate' => $totalCapacity > 0 ? round(($ticketsSold / $totalCapacity) * 100, 2) : 0,
                        'payout_mode' => $payoutSetting->payout_mode ?? null,
                        'gateway' => $payoutSetting->method ?? null,
                    ];
                });

            // Breakdown financeiro por payout mode
            $payoutBreakdown = $events->groupBy('payout_mode')->map(function ($group, $mode) {
                return [
                    'payout_mode' => $mode,
                    'events_count' => $group->count(),
                    'total_revenue' => $group->sum('revenue'),
                    'total_orders' => $group->sum('orders'),
                ];
            })->values();

            // Histórico de vendas (últimos 90 dias)
            $salesHistory = Order::join('events', 'orders.event_id', '=', 'events.id')
                ->where('events.organizer_id', $organizer->id)
                ->where('orders.status', 'paid')
                ->where('orders.updated_at', '>=', now()->subDays(90))
                ->select(
                    DB::raw('DATE(orders.updated_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(orders.total) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'organizer' => [
                    'id' => $organizer->id,
                    'name' => $organizer->name,
                    'email' => $organizer->email,
                    'created_at' => $organizer->created_at,
                ],
                'summary' => $summary,
                'events' => $events,
                'payout_breakdown' => $payoutBreakdown,
                'sales_history' => $salesHistory,
            ];
        });
    }
}
