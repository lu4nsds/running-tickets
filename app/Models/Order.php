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
        'currency',
        'status',
        'payment_gateway',
        'payment_id',
        'metadata',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'metadata' => 'array',
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
}
