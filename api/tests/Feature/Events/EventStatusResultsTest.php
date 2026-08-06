<?php

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusResultsTest extends TestCase
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

    public function test_public_listing_includes_active_and_finished_but_not_inactive(): void
    {
        $active = Event::factory()->create(['status' => EventStatus::ACTIVE->value]);
        $finished = Event::factory()->finished()->create();
        Event::factory()->inactive()->create();

        $slugs = $this->getJson('/api/events')
            ->assertStatus(200)
            ->json('data.*.slug');

        $this->assertContains($active->slug, $slugs);
        $this->assertContains($finished->slug, $slugs);
        $this->assertCount(2, $slugs);
    }

    public function test_public_show_returns_finished_event_with_results_url(): void
    {
        $event = Event::factory()->finished()->create([
            'results_url' => 'https://resultados.exemplo.com/prova',
        ]);

        $this->getJson("/api/events/{$event->slug}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'finished')
            ->assertJsonPath('data.results_url', 'https://resultados.exemplo.com/prova');
    }

    public function test_public_show_404_for_inactive_event(): void
    {
        $event = Event::factory()->inactive()->create();

        $this->getJson("/api/events/{$event->slug}")
            ->assertStatus(404);
    }

    public function test_order_creation_is_blocked_for_finished_event(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->finished()->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $payload = [
            'event_id' => $event->id,
            'items' => [[
                'ticket_type_id' => $ticketType->id,
                'category_id' => $category->id,
                'participant_data' => [
                    'name' => 'Comprador Teste',
                    'email' => 'comprador@test.com',
                    'cpf' => '52998224725',
                    'phone' => '11999990000',
                    'birthdate' => '1990-01-01',
                ],
            ]],
        ];

        $this->postJson('/api/orders', $payload)
            ->assertStatus(422);
    }

    public function test_super_admin_can_update_status_to_finished_with_results_url(): void
    {
        $admin = $this->makeSuperAdmin();
        $event = Event::factory()->create(['status' => EventStatus::ACTIVE->value]);

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}", [
                'status' => 'finished',
                'results_url' => 'https://resultados.exemplo.com/prova',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'finished')
            ->assertJsonPath('data.results_url', 'https://resultados.exemplo.com/prova');
    }

    public function test_super_admin_can_toggle_allows_late_refund_request(): void
    {
        $admin = $this->makeSuperAdmin();
        $event = Event::factory()->create(['status' => EventStatus::ACTIVE->value]);

        $this->assertFalse($event->allows_late_refund_request);

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}", [
                'allows_late_refund_request' => '1',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.allows_late_refund_request', true);

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}", [
                'allows_late_refund_request' => '0',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.allows_late_refund_request', false);
    }

    public function test_update_rejects_invalid_results_url(): void
    {
        $admin = $this->makeSuperAdmin();
        $event = Event::factory()->create(['status' => EventStatus::ACTIVE->value]);

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}", [
                'results_url' => 'not-a-valid-url',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('results_url');
    }

    public function test_created_event_defaults_to_inactive_and_ignores_status(): void
    {
        $admin = $this->makeSuperAdmin();
        $organizer = Organizer::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/events', [
                'organizer_id' => $organizer->id,
                'title' => 'Corrida Nova',
                'slug' => 'corrida-nova',
                'state' => 'SP',
                'city' => 'São Paulo',
                'venue' => 'Parque Ibirapuera',
                'date_start' => now()->addDays(30)->toDateTimeString(),
                'date_end' => now()->addDays(30)->addHours(4)->toDateTimeString(),
                'status' => 'active',
            ])
            ->assertStatus(201);

        $this->assertSame('inactive', Event::find($response->json('data.id'))->status->value);
    }
}
