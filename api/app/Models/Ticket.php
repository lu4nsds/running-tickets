<?php

namespace App\Models;

use App\Enums\TicketStatus;
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
    ];

    protected $casts = [
        'issued_at' => 'datetime',
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
     * Marca o ticket como utilizado
     */
    public function markAsUsed(): void
    {
        $this->update(['status' => TicketStatus::USED]);
    }
}
