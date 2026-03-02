<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'ticket_type_id',
        'category_id',
        'user_id',
        'participant_data',
        'ticket_id',
    ];

    protected $casts = [
        'participant_data' => 'array',
    ];

    /**
     * Compra à qual este participante pertence
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Tipo de inscrição comprada
     */
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Categoria esportiva do atleta
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Usuário associado (login opcional)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ticket gerado para este participante
     */
    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}
