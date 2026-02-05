<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'status',
    ];

    /**
     * Usuários que administram este organizador
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'organizer_users'
        )->withPivot('role')->withTimestamps();
    }

    /**
     * Eventos pertencentes a este organizador
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
