<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'code',
        'qr_path',
        'status',
        'issued_at',
        'validated_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'validated_at' => 'datetime',
        'status' => TicketStatus::class,
    ];

    /**
     * Participante ao qual este ticket pertence
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Ordena os tickets pela data de início do evento associado.
     *
     * Usa uma subquery correlacionada (ticket -> order_item -> order -> event)
     * para garantir uma ordenação determinística no nível do banco, em vez de
     * depender da ordem implícita da chave primária.
     */
    public function scopeOrderByEventDate(Builder $query, string $direction = 'asc'): void
    {
        $query->orderBy(
            OrderItem::query()
                ->select('events.date_start')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('events', 'events.id', '=', 'orders.event_id')
                ->whereColumn('order_items.id', 'tickets.order_item_id')
                ->limit(1),
            $direction,
        );
    }

    /**
     * Ordena os tickets pela data de compra (data de criação do pedido).
     *
     * Usa a mesma técnica de subquery correlacionada do scopeOrderByEventDate,
     * apontando para orders.created_at (data real da compra), e não para
     * tickets.created_at, que é gerado de forma assíncrona após o pagamento.
     * Default desc: comprados mais recentemente aparecem primeiro.
     */
    public function scopeOrderByPurchaseDate(Builder $query, string $direction = 'desc'): void
    {
        $query->orderBy(
            OrderItem::query()
                ->select('orders.created_at')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereColumn('order_items.id', 'tickets.order_item_id')
                ->limit(1),
            $direction,
        );
    }

    /**
     * Gera um código único para o ticket
     */
    public static function generateCode(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Verifica se o ticket pode ser usado
     */
    public function canBeUsed(): bool
    {
        return $this->status === TicketStatus::ACTIVE;
    }

    /**
     * Marca o ticket como utilizado, registrando o momento do check-in.
     *
     * `validated_at` é a âncora do envio de feedback pós-evento — não confie
     * em `updated_at` para isso, qualquer outra escrita no ticket o altera.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'status' => TicketStatus::USED,
            'validated_at' => now(),
        ]);
    }
}
