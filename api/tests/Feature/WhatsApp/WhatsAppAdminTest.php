<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AdminUser;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        // Mock do WhatsAppService para todos os testes desta suite
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('connect')->andReturn(['status' => 'connecting', 'qr' => 'raw-qr']);
            $mock->shouldReceive('status')->andReturn(['status' => 'open', 'qr' => null]);
            $mock->shouldReceive('disconnect')->andReturn(true);
        });
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_connect_requires_super_admin(): void
    {
        $user = AdminUser::factory()->create();

        $this->actingAs($user, 'admin')
            ->postJson('/api/admin/whatsapp/connect')
            ->assertStatus(403);
    }

    public function test_status_requires_super_admin(): void
    {
        $user = AdminUser::factory()->create();

        $this->actingAs($user, 'admin')
            ->getJson('/api/admin/whatsapp/status')
            ->assertStatus(403);
    }

    public function test_disconnect_requires_super_admin(): void
    {
        $user = AdminUser::factory()->create();

        $this->actingAs($user, 'admin')
            ->deleteJson('/api/admin/whatsapp/session')
            ->assertStatus(403);
    }

    // ── Endpoints ────────────────────────────────────────────────────────────

    public function test_connect_returns_status_and_qr(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/whatsapp/connect')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'connecting')
            ->assertJsonPath('data.qr', 'raw-qr');
    }

    public function test_status_returns_current_session_state(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/whatsapp/status')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'open');
    }

    public function test_disconnect_returns_204(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->deleteJson('/api/admin/whatsapp/session')
            ->assertStatus(204);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeSuperAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create();

        \DB::table('roles')->insertOrIgnore(['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]);
        $role = \DB::table('roles')->where('slug', 'super_admin')->first();
        \DB::table('admin_user_roles')->insertOrIgnore(['admin_user_id' => $admin->id, 'role_id' => $role->id]);

        return $admin->fresh();
    }
}
