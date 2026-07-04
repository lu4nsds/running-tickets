<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Usuários (portal) que possuem este papel
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * Usuários de backoffice que possuem este papel
     */
    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_roles')
            ->withTimestamps();
    }
}
