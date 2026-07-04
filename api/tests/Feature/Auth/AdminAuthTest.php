<?php

namespace Tests\Feature\Auth;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_login_via_admin_endpoint(): void
    {
        $admin = AdminUser::factory()->create([
            'email' => 'admin@backoffice.test',
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@backoffice.test',
            'password' => 'Secret123',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $admin->id)
            ->assertJsonStructure(['user', 'access_token', 'token_type']);
    }

    public function test_portal_user_cannot_login_via_admin_endpoint(): void
    {
        // Mesmo e-mail existe apenas na tabela `users` (portal)
        User::factory()->create([
            'email' => 'buyer@portal.test',
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/admin/auth/login', [
            'email' => 'buyer@portal.test',
            'password' => 'Secret123',
        ])->assertStatus(422);
    }

    public function test_admin_token_is_rejected_on_portal_route(): void
    {
        $admin = AdminUser::factory()->create();

        // Token de admin não deve autenticar no guard client
        $this->actingAs($admin, 'admin')
            ->getJson('/api/orders')
            ->assertStatus(401);
    }

    public function test_portal_token_is_rejected_on_admin_route(): void
    {
        $user = User::factory()->create();

        // Token do portal não deve autenticar no guard admin
        $this->actingAs($user, 'client')
            ->getJson('/api/admin/auth/me')
            ->assertStatus(401);
    }

    public function test_admin_me_returns_roles_and_organizers(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $admin->id)
            ->assertJsonStructure(['id', 'name', 'email', 'roles', 'organizers']);
    }
}
