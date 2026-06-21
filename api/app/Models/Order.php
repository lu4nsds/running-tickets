<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'organizer_id',
        'reference',
        'user_id',
        'total_cents',
        'fee_cents',
        'net_amount_cents',
        'currency',
        'status',
        'buyer_email',
        'buyer_phone',
        'payment_gateway',
        'payment_id',
        'payment_response_body',
        'metadata',
        'reserved_until',
    ];

    protected $casts = [
        'status'                => OrderStatus::class,
        'metadata'              => 'array',
        'payment_response_body' => 'array',
        'fee_cents'        => 'integer',
        'net_amount_cents' => 'integer',
        'reserved_until'   => 'datetime',
    ];

    /**
     * Get the route key for the model.
     * Use 'reference' instead of 'id' for secure URLs
     */
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Evento ao qual a compra pertence
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Organizador (dono do evento)
     */
    public function organizer()
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Usuário comprador (opcional)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Participantes inscritos nesta compra
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Solicitações de cancelamento/estorno deste pedido
     */
    public function cancellations()
    {
        return $this->hasMany(OrderCancellation::class);
    }

    /**
     * Última solicitação de cancelamento (para exibir status ao comprador)
     */
    public function latestCancellation()
    {
        return $this->hasOne(OrderCancellation::class)->latestOfMany();
    }

    /**
     * Gera uma referência única para o pedido
     */
    public static function generateReference(): string
    {
        $year = now()->year;
        $random = strtoupper(Str::random(8));
        
        return "ORD-{$year}-{$random}";
    }

    /**
     * Calcula o total do pedido baseado nos itens
     */
    public function calculateTotal(): int
    {
        return $this->items->sum(function ($item) {
            return $item->ticketType->price_cents;
        });
    }

    /**
     * Verifica se o pedido pode ser cancelado
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [OrderStatus::PENDING, OrderStatus::PAID]);
    }

    /**
     * Verifica se o pedido está pago
     */
    public function isPaid(): bool
    {
        return $this->status === OrderStatus::PAID;
    }

    /**
     * Verifica se há uma solicitação de cancelamento ainda pendente
     */
    public function hasPendingCancellation(): bool
    {
        return $this->cancellations()
            ->where('status', \App\Enums\OrderCancellationStatus::PENDING)
            ->exists();
    }

    public function hasActiveReservation(): bool
    {
        return $this->reserved_until !== null && $this->reserved_until->isFuture();
    }

    public function isPayable(): bool
    {
        return $this->status->isPayable() && $this->hasActiveReservation();
    }

    public function getPendingPixData(): ?array
    {
        $body = $this->payment_response_body ?? [];

        if (($body['outcome'] ?? null) !== 'pending') {
            return null;
        }

        if (($body['mp_status'] ?? null) !== 'pending') {
            return null;
        }

        if (empty($body['qr_code'])) {
            return null;
        }

        return $body;
    }

    public function scopeReservedOrders($query)
    {
        return $query->whereIn('status', [
            OrderStatus::PENDING->value,
            OrderStatus::PROCESSING->value,
            OrderStatus::FAILED->value,
        ])->where('reserved_until', '>', now());
    }
}
