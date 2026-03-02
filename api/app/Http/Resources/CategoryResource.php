<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'event_id' => $this->event_id,
            'name' => $this->name,
            'distance' => $this->distance,
            'description' => $this->description,
            'gender' => $this->gender,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Contadores (quando carregados via withCount)
            'participants_count' => $this->when(isset($this->order_items_count), $this->order_items_count ?? 0),
            
            // Relacionamentos opcionais
            'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
