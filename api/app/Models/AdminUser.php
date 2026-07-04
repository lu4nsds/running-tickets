<?php

namespace App\Models;

use App\Enums\OrganizerRole;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuário de backoffice (app admin): super admin ou admin/staff de organizador.
 * Autenticado pelo guard `admin`. Não compra ingressos nem usa Google OAuth.
 */
class AdminUser extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\AdminUserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Papéis globais do usuário de backoffice
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_user_roles')
            ->withTimestamps();
    }

    /**
     * Organizadores aos quais o usuário pertence (multi-tenant)
     */
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class, 'organizer_users', 'admin_user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Verifica se o usuário tem um papel específico
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Verifica se o usuário tem algum dos papéis informados
     */
    public function hasAnyRole(array $slugs): bool
    {
        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    /**
     * Atribui um papel ao usuário
     */
    public function assignRole(string $slug): void
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        $this->roles()->syncWithoutDetaching($role);
    }

    /**
     * Remove um papel do usuário
     */
    public function removeRole(string $slug): void
    {
        $role = Role::where('slug', $slug)->first();
        if ($role) {
            $this->roles()->detach($role);
        }
    }

    /**
     * Verifica se o usuário é super administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN->value);
    }

    /**
     * Verifica se o usuário tem acesso a um organizador específico
     */
    public function canAccessOrganizer(int $organizerId): bool
    {
        return $this->isSuperAdmin()
            || $this->organizers()->where('organizer_id', $organizerId)->exists();
    }

    /**
     * Verifica se o usuário é admin de um organizador
     */
    public function isOrganizerAdmin(int $organizerId): bool
    {
        return $this->organizers()
            ->where('organizer_id', $organizerId)
            ->wherePivot('role', OrganizerRole::ADMIN->value)
            ->exists();
    }

    /**
     * Verifica se o usuário é staff de um organizador
     */
    public function isOrganizerStaff(int $organizerId): bool
    {
        return $this->organizers()
            ->where('organizer_id', $organizerId)
            ->wherePivot('role', OrganizerRole::STAFF->value)
            ->exists();
    }
}
