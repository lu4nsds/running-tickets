<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'allows_shirt_size',
        'requires_senior_age',
        'senior_min_age',
    ];

    protected $casts = [
        'attributes' => 'array',
        'start_sale' => 'datetime',
        'end_sale' => 'datetime',
        'active' => 'boolean',
        'allows_shirt_size' => 'boolean',
        'requires_senior_age' => 'boolean',
        'senior_min_age' => 'integer',
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
     * Categorias vinculadas a este tipo de ingresso.
     *
     * Quando vazio, o checkout libera todas as categorias ativas do evento.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'ticket_type_categories');
    }

    /**
     * Verifica se um participante nascido em `$birthdate` atende à exigência
     * etária deste lote.
     *
     * A idade é contada em anos completos na data de referência — por padrão a
     * data de início do evento, e não a data da compra, para não barrar quem
     * completa a idade entre a inscrição e a prova.
     *
     * Lotes sem exigência etária sempre aceitam.
     */
    public function acceptsParticipantBornOn(?string $birthdate, CarbonInterface|string|null $referenceDate = null): bool
    {
        if (! $this->requires_senior_age) {
            return true;
        }

        if (! $birthdate) {
            return false;
        }

        $born = Carbon::parse($birthdate)->startOfDay();
        $reference = Carbon::parse($referenceDate ?? $this->event?->date_start ?? now())->startOfDay();

        if ($reference->lt($born)) {
            return false;
        }

        // diffInYears devolve float no Carbon 3; o cast trunca para anos completos.
        return (int) $born->diffInYears($reference) >= $this->senior_min_age;
    }

    /**
     * Verifica se este tipo de ingresso está disponível para compra
     */
    public function isAvailableForPurchase(): bool
    {
        if (! $this->active) {
            return false;
        }

        $now = now();
        if ($this->start_sale && $now->lt($this->start_sale)) {
            return false;
        }
        if ($this->end_sale && $now->gt($this->end_sale)) {
            return false;
        }

        if ($this->quota !== null && $this->getAvailableQuantity() <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Retorna quantidade disponível para venda.
     *
     * Considera ingressos já emitidos (PAID) e reservas ativas
     * (PENDING/PROCESSING/FAILED com reserved_until > now()) — isso protege
     * contra overselling durante o fluxo de pagamento assíncrono.
     */
    public function getAvailableQuantity(): ?int
    {
        if ($this->quota === null) {
            return null;
        }

        $taken = $this->orderItems()
            ->whereHas('order', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', OrderStatus::PAID->value)
                        ->orWhere(function ($q2) {
                            $q2->whereIn('status', [
                                OrderStatus::PENDING->value,
                                OrderStatus::PROCESSING->value,
                                OrderStatus::FAILED->value,
                            ])->where('reserved_until', '>', now());
                        });
                });
            })
            ->count();

        return max(0, $this->quota - $taken);
    }
}
