<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $soldCount = $this->sold_count ?? $this->order_items_count ?? 0;
        $available = $this->quota ? max(0, $this->quota - $soldCount) : null;
        $soldPercentage = $this->quota && $this->quota > 0 ? round(($soldCount / $this->quota) * 100) : 0;
        
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'quota' => $this->quota,
            'start_sale' => $this->start_sale,
            'end_sale' => $this->end_sale,
            'attributes' => $this->attributes,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Contadores de vendas
            'sold_count' => $soldCount,
            'available' => $available,
            'is_sold_out' => $this->quota && $soldCount >= $this->quota,
            'sold_percentage' => $soldPercentage,
            
            // Relacionamentos opcionais
            'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
