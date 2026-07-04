<?php

namespace App\Models;

use App\Enums\OrderCancellationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'requested_by',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'refund_id',
        'review_notes',
    ];

    protected $casts = [
        'status' => OrderCancellationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /**
     * Pedido alvo da solicitação
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Usuário que solicitou o cancelamento (opcional)
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Super admin (backoffice) que avaliou a solicitação
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }

    /**
     * Solicitações ainda pendentes de avaliação
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', OrderCancellationStatus::PENDING);
    }
}
