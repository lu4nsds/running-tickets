<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'state' => $this->state,
            'city' => $this->city,
            'venue' => $this->venue,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'max_participants' => $this->max_participants,
            'banner_url' => $this->banner_full_url,
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Estatísticas (quando carregadas via withCount/aggregate)
            'participants_count' => $this->when(isset($this->participants_count), $this->participants_count ?? 0),
            'total_revenue'     => $this->when(isset($this->total_revenue), $this->total_revenue ?? 0),
            'total_net_revenue' => $this->when(isset($this->total_net_revenue), $this->total_net_revenue ?? 0),
            'remaining_spots' => $this->when(
                isset($this->participants_count) && $this->max_participants,
                fn() => max(0, $this->max_participants - ($this->participants_count ?? 0))
            ),
            'ticket_stats' => $this->when(isset($this->ticket_stats), $this->ticket_stats),
            
            // Configuração de pagamento
            'payout_mode' => $this->whenLoaded('payoutSetting', function () {
                return $this->payoutSetting?->payout_mode;
            }),
            'payout_provider' => $this->whenLoaded('payoutSetting', function () {
                return $this->payoutSetting?->provider;
            }),
            
            // Relacionamentos
            'organizer' => $this->whenLoaded('organizer', function () {
                return new OrganizerResource($this->organizer);
            }),
            'categories' => $this->whenLoaded('categories', function () {
                return CategoryResource::collection($this->categories);
            }),
            'ticket_types' => $this->whenLoaded('ticketTypes', function () {
                return TicketTypeResource::collection($this->ticketTypes);
            }),
        ];
    }
}
