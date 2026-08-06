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
            'events' => $this->whenLoaded('events', function () {
                return EventResource::collection($this->events);
            }),
            'events_count' => $this->when(isset($this->events_count), $this->events_count),
            'split_events_count' => $this->when(isset($this->split_events_count), $this->split_events_count),

            // Conexão com o gateway (split). Null quando o organizador ainda não
            // conectou. O resource abaixo mascara o ID e nunca expõe tokens.
            'payment_account' => $this->whenLoaded(
                'paymentAccount',
                fn () => $this->paymentAccount
                    ? new PaymentGatewayAccountResource($this->paymentAccount)
                    : null
            ),

            // Stats calculados
            'total_sales' => $this->when(isset($this->total_sales), $this->total_sales),
            'total_net_sales' => $this->when(isset($this->total_net_sales), $this->total_net_sales),
        ];
    }
}
