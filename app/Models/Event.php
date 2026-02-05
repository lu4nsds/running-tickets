<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'title',
        'slug',
        'description',
        'city',
        'venue',
        'date_start',
        'date_end',
        'max_participants',
        'banner_url',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
    ];

    /**
     * Organizador dono do evento
     */
    public function organizer()
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Categorias esportivas do evento
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Tipos de inscrição disponíveis
     */
    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * Pedidos realizados para este evento
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Configuração de recebimento do evento
     */
    public function payoutSettings()
    {
        return $this->hasMany(EventPayoutSetting::class);
    }

    /**
     * Configuração de pagamento ativa do evento
     */
    public function payoutSetting()
    {
        return $this->hasOne(EventPayoutSetting::class)->where('active', true);
    }
}
