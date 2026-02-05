<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'gender',
        'min_age',
        'max_age',
        'active',
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
}
