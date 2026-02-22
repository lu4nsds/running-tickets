<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventPayoutSetting;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        
        // Remover payout_mode e banner dos dados do evento
        $payoutMode = $data['payout_mode'] ?? null;
        unset($data['payout_mode']);
        unset($data['banner']);
        
        // Processar upload do banner
        if ($request->hasFile('banner')) {
            $data['banner_url'] = $request->file('banner')->store('events/banners', 'public');
        }
        
        // Gerar slug se não fornecido
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        
        $event = Event::create($data);

        // Se payout_mode foi fornecido, criar configuração de pagamento
        if ($payoutMode) {
            $this->createPayoutSetting($event, $payoutMode);
        }

        return new EventResource($event->load('organizer'));
    }

    /**
     * Criar configuração de pagamento para o evento
     */
    private function createPayoutSetting(Event $event, string $payoutMode): void
    {
        if ($payoutMode === 'platform') {
            // Modo Platform: usar credenciais da plataforma
            try {
                $platformCredentials = MercadoPagoService::getPlatformCredentials();
                
                EventPayoutSetting::create([
                    'event_id' => $event->id,
                    'method' => 'mercadopago',
                    'payout_mode' => 'platform',
                    'provider' => 'Mercado Pago',
                    'details' => $platformCredentials,
                    'active' => true,
                ]);

                // Ativar evento automaticamente
                $event->update(['status' => 'ativo']);
            } catch (\Exception $e) {
                // Se falhar, não bloqueia criação do evento
                \Log::warning('Erro ao configurar payout platform na criação do evento', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // Modo Direct: criar setting vazio, organizador adiciona credenciais depois
            EventPayoutSetting::create([
                'event_id' => $event->id,
                'method' => 'mercadopago',
                'payout_mode' => 'direct',
                'provider' => 'Mercado Pago',
                'details' => [],
                'active' => true,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        // Carregar relacionamentos com contagens
        $event->load([
            'organizer',
            'categories' => function ($query) {
                $query->withCount('orderItems');
            },
            'ticketTypes' => function ($query) {
                $query->withCount('orderItems');
            },
        ]);
        
        // Calcular estatísticas do evento
        $event->participants_count = $event->orders()
            ->where('status', 'paid')
            ->withCount('items')
            ->get()
            ->sum('items_count');
            
        $event->total_revenue = $event->orders()
            ->where('status', 'paid')
            ->sum('total_cents');
        
        // Estatísticas de tickets (validação)
        $event->ticket_stats = $event->getTicketStatistics();
        
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->validated();
        unset($data['banner']);
        
        // Processar upload do banner
        if ($request->hasFile('banner')) {
            // Deletar banner anterior se for path local
            if ($event->banner_url && !str_starts_with($event->banner_url, 'http')) {
                Storage::disk('public')->delete($event->banner_url);
            }
            $data['banner_url'] = $request->file('banner')->store('events/banners', 'public');
        }
        
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
