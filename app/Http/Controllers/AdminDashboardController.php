<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Resolve o range de datas baseado no preset ou datas customizadas
     */
    private function resolveDateRange(Request $request): array
    {
        $preset = $request->input('preset', 'current_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($preset === 'custom' && $startDate && $endDate) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
                'custom',
            ];
        }

        $now = Carbon::now();

        return match ($preset) {
            'current_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'current_month'],
            'current_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay(), 'current_year'],
            '7d' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay(), '7d'],
            '30d' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay(), '30d'],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'current_month'],
        };
    }

    /**
     * Dashboard geral da plataforma (visão admin)
     */
    public function index(Request $request)
    {
        // Validar parâmetros de filtro
        $request->validate([
            'preset' => 'nullable|in:current_month,current_year,7d,30d,custom',
            'start_date' => 'nullable|date|required_if:preset,custom',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:preset,custom',
        ]);

        [$startDate, $endDate, $presetKey] = $this->resolveDateRange($request);
        
        $cacheKey = "dashboard_admin_{$presetKey}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            // Resumo da plataforma - filtrado por período
            $platformSummary = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'paid' THEN total_cents ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'pending' THEN total_cents ELSE 0 END) as pending_revenue
                ")->first();

            // Estatísticas de eventos e organizadores - filtrado por período
            $eventStats = [
                'total_organizers' => Organizer::whereBetween('created_at', [$startDate, $endDate])->count(),
                'total_events' => Event::whereBetween('created_at', [$startDate, $endDate])->count(),
                'active_events' => Event::where('status', 'ativo')->whereBetween('created_at', [$startDate, $endDate])->count(),
                'events_in_period' => Event::whereBetween('created_at', [$startDate, $endDate])->count(),
            ];

            // Ranking de organizadores por faturamento (top 10) - filtrado por período
            $topOrganizers = DB::table('organizers')
                ->leftJoin('events', 'organizers.id', '=', 'events.organizer_id')
                ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                    $join->on('events.id', '=', 'orders.event_id')
                        ->where('orders.status', '=', 'paid')
                        ->whereBetween('orders.created_at', [$startDate, $endDate]);
                })
                ->select(
                    'organizers.id',
                    'organizers.name',
                    DB::raw('COUNT(DISTINCT events.id) as total_events'),
                    DB::raw('COUNT(orders.id) as total_orders'),
                    DB::raw('COALESCE(SUM(orders.total_cents), 0) as total_revenue')
                )
                ->groupBy('organizers.id', 'organizers.name')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            // Breakdown financeiro por modo de pagamento - filtrado por período
            $payoutBreakdown = DB::table('orders')
                ->join('events', 'orders.event_id', '=', 'events.id')
                ->join('event_payout_settings', function ($join) {
                    $join->on('events.id', '=', 'event_payout_settings.event_id')
                        ->where('event_payout_settings.active', '=', true);
                })
                ->where('orders.status', 'paid')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->select(
                    'event_payout_settings.payout_mode',
                    'event_payout_settings.method as gateway',
                    DB::raw('COUNT(orders.id) as orders_count'),
                    DB::raw('SUM(orders.total_cents) as revenue')
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
                ->leftJoin(DB::raw('(SELECT organizer_id, MIN(date_start) as nearest_event_date 
                    FROM events 
                    WHERE date_start >= NOW() 
                    GROUP BY organizer_id) as nearest_events'), 
                    'organizers.id', '=', 'nearest_events.organizer_id')
                ->where('orders.status', 'paid')
                ->select(
                    'organizers.id as organizer_id',
                    'organizers.name as organizer_name',
                    DB::raw('COUNT(orders.id) as orders_count'),
                    DB::raw('SUM(orders.total_cents) as amount_to_transfer'),
                    DB::raw('DATEDIFF(nearest_events.nearest_event_date, NOW()) as nearest_event_days')
                )
                ->groupBy('organizers.id', 'organizers.name', 'nearest_events.nearest_event_date')
                ->orderByDesc('amount_to_transfer')
                ->get();

            // Saúde da plataforma - filtrado por período
            $platformHealth = Order::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                ")
                ->first();

            $healthMetrics = [
                'conversion_rate' => $platformHealth->total_orders > 0 
                    ? round(($platformHealth->paid_orders / $platformHealth->total_orders) * 100, 2) 
                    : 0,
                'cancellation_rate' => $platformHealth->total_orders > 0 
                    ? round(($platformHealth->cancelled_orders / $platformHealth->total_orders) * 100, 2) 
                    : 0,
                'avg_order_value' => $platformSummary->paid_orders > 0 
                    ? round($platformSummary->total_revenue / $platformSummary->paid_orders) 
                    : 0,
            ];

            // Tendência de vendas - filtrado por período
            $salesTrend = Order::where('status', 'paid')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(updated_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total_cents) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Alertas (eventos próximos com baixo desempenho de vendas)
            $alerts = Event::where('date_start', '>=', now())
                ->where('date_start', '<=', now()->addDays(30))
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
                        ->sum('quota');

                    // Alertar se vendeu menos de 30% da capacidade com menos de 30 dias para o evento
                    $ticketsSold = DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
                        ->where('ticket_types.event_id', $event->id)
                        ->where('orders.status', 'paid')
                        ->count();

                    $occupancyRate = $totalCapacity > 0 ? ($ticketsSold / $totalCapacity) * 100 : 0;
                    
                    return $occupancyRate < 30;
                })
                ->map(function ($event) {
                    $totalCapacity = DB::table('ticket_types')
                        ->where('event_id', $event->id)
                        ->sum('quota');

                    $ticketsSold = DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
                        ->where('ticket_types.event_id', $event->id)
                        ->where('orders.status', 'paid')
                        ->count();

                    return [
                        'event_id' => $event->id,
                        'event_name' => $event->title,
                        'event_date' => $event->date_start,
                        'days_until_event' => now()->diffInDays($event->date_start, false),
                        'tickets_sold' => $ticketsSold,
                        'total_capacity' => $totalCapacity,
                        'occupancy_rate' => round(($ticketsSold / $totalCapacity) * 100, 2),
                        'severity' => $ticketsSold < ($totalCapacity * 0.1) ? 'high' : 'medium',
                    ];
                })
                ->values();

            // Eventos próximos (próximos 10 eventos ordenados por data)
            $upcomingEvents = Event::where('date_start', '>=', now())
                ->join('organizers', 'events.organizer_id', '=', 'organizers.id')
                ->orderBy('date_start', 'asc')
                ->limit(10)
                ->select(
                    'events.id',
                    'events.title',
                    'events.date_start',
                    'organizers.name as organizer_name',
                    DB::raw('DATEDIFF(events.date_start, NOW()) as days_until')
                )
                ->get();

            return [
                'summary' => array_merge((array) $platformSummary, $eventStats),
                'top_organizers' => $topOrganizers,
                'payout_breakdown' => $payoutBreakdown,
                'pending_payouts' => $pendingPayouts,
                'platform_health' => $healthMetrics,
                'sales_trend' => $salesTrend,
                'alerts' => $alerts,
                'upcoming_events' => $upcomingEvents,
                'applied_filters' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
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
                    COALESCE(SUM(orders.total_cents), 0) as total_revenue
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
                ], 'total_cents')
                ->get()
                ->map(function ($event) {
                    $totalCapacity = DB::table('ticket_types')
                        ->where('event_id', $event->id)
                        ->sum('quota');

                    $ticketsSold = DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
                        ->where('ticket_types.event_id', $event->id)
                        ->where('orders.status', 'paid')
                        ->count();

                    // Buscar configuração de payout
                    $payoutSetting = DB::table('event_payout_settings')
                        ->where('event_id', $event->id)
                        ->where('active', true)
                        ->first();

                    return [
                        'id' => $event->id,
                        'name' => $event->title,
                        'date' => $event->date_start,
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
                    DB::raw('SUM(orders.total_cents) as revenue')
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
