<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'status' => EventStatus::class,
    ];

    /**
     * Accessor para URL completa do banner
     * Suporta tanto URLs externas quanto paths locais do Storage
     */
    public function getBannerFullUrlAttribute(): ?string
    {
        if (!$this->banner_url) {
            return null;
        }

        // Se já é URL completa (http/https), retorna direto
        if (str_starts_with($this->banner_url, 'http')) {
            return $this->banner_url;
        }

        // Se é path local, gera URL via Storage
        return asset('storage/' . $this->banner_url);
    }

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
