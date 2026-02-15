<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerResource extends JsonResource
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
            'name' => $this->name,
            'document' => $this->document,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'address_complement' => $this->address_complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relacionamentos opcionais
            'users' => $this->whenLoaded('users'),
            'events' => $this->whenLoaded('events'),
            'events_count' => $this->when(isset($this->events_count), $this->events_count),
        ];
    }
}
