<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'distance',
        'description',
        'gender',
        'min_age',
        'max_age',
        'active',
    ];

    protected $casts = [
        'distance' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Evento ao qual a categoria pertence
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Participantes inscritos nessa categoria
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Tipos de ingresso aos quais esta categoria está vinculada
     */
    public function ticketTypes(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class, 'ticket_type_categories');
    }
}
