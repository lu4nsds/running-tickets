<?php

namespace Tests\Feature\Settings;

use App\Enums\PlatformSettingKey;
use App\Models\AdminUser;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): AdminUser
    {
        $user = AdminUser::factory()->create();

        \DB::table('roles')->insertOrIgnore(['name' => 'Super Admin', 'slug' => 'super_admin', 'created_at' => now(), 'updated_at' => now()]);
        $role = \DB::table('roles')->where('slug', 'super_admin')->first();
        \DB::table('admin_user_roles')->insertOrIgnore(['admin_user_id' => $user->id, 'role_id' => $role->id]);

        return $user->fresh();
    }

    public function test_super_admin_can_read_and_update_feedback_form_url(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.feedback_form_url', null);

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/settings', [
                'feedback_form_url' => 'https://forms.gle/abc123',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.feedback_form_url', 'https://forms.gle/abc123');

        // Persistiu e continua disponível numa nova leitura
        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/settings')
            ->assertStatus(200)
            ->assertJsonPath('data.feedback_form_url', 'https://forms.gle/abc123');

        $this->assertSame(
            'https://forms.gle/abc123',
            PlatformSetting::getValue(PlatformSettingKey::FEEDBACK_FORM_URL),
        );
    }

    public function test_blank_value_clears_the_setting(): void
    {
        $admin = $this->makeSuperAdmin();
        PlatformSetting::setValue(PlatformSettingKey::FEEDBACK_FORM_URL, 'https://forms.gle/abc123');

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/settings', ['feedback_form_url' => ''])
            ->assertStatus(200)
            ->assertJsonPath('data.feedback_form_url', null);

        $this->assertNull(PlatformSetting::getValue(PlatformSettingKey::FEEDBACK_FORM_URL));
    }

    public function test_invalid_url_is_rejected(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/settings', ['feedback_form_url' => 'nao-e-uma-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('feedback_form_url');
    }

    public function test_non_super_admin_cannot_access_settings(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/settings')
            ->assertStatus(403);

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/settings', ['feedback_form_url' => 'https://forms.gle/abc123'])
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_settings(): void
    {
        $this->getJson('/api/admin/settings')->assertStatus(401);
    }
}
