<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'ticket_type' => TicketTypeResource::make($this->whenLoaded('ticketType')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'participant_data' => $this->participant_data,
            'ticket' => TicketResource::make($this->whenLoaded('ticket')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
