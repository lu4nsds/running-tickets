<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expõe o status da conexão do organizador com o gateway, sem jamais revelar
 * os tokens (access/refresh ficam sempre ocultos).
 */
class PaymentGatewayAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'gateway' => $this->gateway->value,
            'status' => $this->status,
            'connected' => $this->isConnected(),
            'provider_account_id' => $this->maskAccountId(),
            'has_public_key' => ! empty($this->public_key),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'connected_at' => $this->connected_at?->toIso8601String(),
        ];
    }

    private function maskAccountId(): ?string
    {
        if (empty($this->provider_account_id)) {
            return null;
        }

        $id = (string) $this->provider_account_id;

        return strlen($id) <= 4
            ? str_repeat('•', strlen($id))
            : str_repeat('•', strlen($id) - 4).substr($id, -4);
    }
}
