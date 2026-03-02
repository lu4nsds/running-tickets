<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'reference' => $this->reference,
            'event' => EventResource::make($this->whenLoaded('event')),
            'organizer' => OrganizerResource::make($this->whenLoaded('organizer')),
            'user_id' => $this->user_id,
            'total_cents' => $this->total_cents,
            'total_formatted' => 'R$ ' . number_format($this->total_cents / 100, 2, ',', '.'),
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_gateway' => $this->payment_gateway,
            'payment_id' => $this->payment_id,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
