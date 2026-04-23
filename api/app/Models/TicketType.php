<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price_cents',
        'currency',
        'quota',
        'start_sale',
        'end_sale',
        'attributes',
        'active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'start_sale' => 'datetime',
        'end_sale' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Evento ao qual esse ticket pertence
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Inscrições realizadas com este ticket
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Verifica se este tipo de ingresso está disponível para compra
     */
    public function isAvailableForPurchase(): bool
    {
        // Verifica se está ativo
        if (!$this->active) {
            return false;
        }

        // Verifica se está no período de vendas
        $now = now();
        if ($this->start_sale && $now->lt($this->start_sale)) {
            return false;
        }
        if ($this->end_sale && $now->gt($this->end_sale)) {
            return false;
        }

        // Verifica se ainda tem quota disponível
        if ($this->quota !== null) {
            $sold = $this->orderItems()
                ->whereHas('order', function ($query) {
                    $query->where('status', 'paid');
                })
                ->count();

            if ($sold >= $this->quota) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retorna quantidade disponível para venda
     */
    public function getAvailableQuantity(): ?int
    {
        if ($this->quota === null) {
            return null; // ilimitado
        }

        $sold = $this->orderItems()
            ->whereHas('order', function ($query) {
                $query->where('status', 'paid');
            })
            ->count();

        return max(0, $this->quota - $sold);
    }
}
