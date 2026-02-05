<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventPayoutSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'method',
        'payout_mode',
        'provider',
        'details',
        'active',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'details' => 'array',
        'active' => 'boolean',
    ];

    // Relacionamento com Event
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
