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
            'total_formatted' => 'R$ '.number_format($this->total_cents / 100, 2, ',', '.'),
            'fee_cents' => $this->fee_cents,
            'net_amount_cents' => $this->net_amount_cents,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'reserved_until' => $this->reserved_until?->toIso8601String(),
            'is_payable' => $this->isPayable(),
            'buyer_email' => $this->buyer_email,
            'buyer_phone' => $this->buyer_phone,
            'payment_gateway' => $this->payment_gateway,
            'payment_id' => $this->payment_id,
            'payment_method_label' => $this->paymentMethodLabel(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'cancellation' => $this->whenLoaded(
                'latestCancellation',
                fn () => $this->latestCancellation
                    ? OrderCancellationResource::make($this->latestCancellation)
                    : null,
            ),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
