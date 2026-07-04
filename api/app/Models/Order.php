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
        'paid_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'metadata' => 'array',
        'payment_response_body' => 'array',
        'fee_cents' => 'integer',
        'net_amount_cents' => 'integer',
        'reserved_until' => 'datetime',
        'paid_at' => 'datetime',
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

    /**
     * Regra de negócio (fonte única) para solicitação de cancelamento/estorno:
     * pedido pago, dentro da janela de 7 dias da confirmação do pagamento, com
     * ao menos um ingresso ativo e sem solicitação pendente/aprovada.
     */
    public function canRequestCancellation(): bool
    {
        return $this->isPaid()
            && $this->paid_at !== null
            && $this->paid_at->gt(now()->subDays(7))
            && ! $this->hasPendingCancellation()
            && ! $this->cancellations()
                ->where('status', \App\Enums\OrderCancellationStatus::APPROVED)
                ->exists()
            && $this->items()
                ->whereHas('ticket', fn ($q) => $q->where('status', \App\Enums\TicketStatus::ACTIVE->value))
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

    /**
     * Rótulo amigável de "pago com" derivado da resposta do gateway.
     * Ex.: "Cartão de crédito", "PIX", "Boleto". Null quando desconhecido.
     */
    public function paymentMethodLabel(): ?string
    {
        $body = $this->payment_response_body ?? [];
        $type = $body['payment_type'] ?? null;
        $method = $body['payment_method'] ?? ($this->metadata['payment_method'] ?? null);

        return match (true) {
            $type === 'credit_card', $method === 'credit_card' => 'Cartão de crédito',
            $type === 'debit_card', $method === 'debit_card' => 'Cartão de débito',
            $type === 'bank_transfer', $method === 'pix' => 'PIX',
            $type === 'ticket', in_array($method, ['bolbradesco', 'boleto'], true) => 'Boleto',
            default => null,
        };
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
