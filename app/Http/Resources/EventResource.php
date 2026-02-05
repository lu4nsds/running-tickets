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
            'city' => $this->city,
            'venue' => $this->venue,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'max_participants' => $this->max_participants,
            'banner_url' => $this->banner_url,
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relacionamentos opcionais
            'organizer' => new OrganizerResource($this->whenLoaded('organizer')),
            'categories' => $this->whenLoaded('categories'),
            'ticket_types' => $this->whenLoaded('ticketTypes'),
        ];
    }
}
