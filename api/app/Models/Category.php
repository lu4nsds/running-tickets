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
}
