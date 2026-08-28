<?php

namespace Tests\Feature\Orders;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeniorTicketTypeTest extends TestCase
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

    private function participant(string $birthdate, string $cpf = '52998224725'): array
    {
        return [
            'name' => 'Participante Teste',
            'email' => 'participante@test.com',
            'cpf' => $cpf,
            'phone' => '11999990000',
            'birthdate' => $birthdate,
            'gender' => 'M',
        ];
    }

    private function payload(Event $event, TicketType $ticketType, Category $category, string $birthdate): array
    {
        return [
            'event_id' => $event->id,
            'items' => [[
                'ticket_type_id' => $ticketType->id,
                'category_id' => $category->id,
                'participant_data' => $this->participant($birthdate),
            ]],
        ];
    }

    public function test_ticket_type_without_the_flag_accepts_any_age(): void
    {
        $event = Event::factory()->for(Organizer::factory())->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->postJson('/api/orders', $this->payload(
            $event, $ticketType, $category, now()->subYears(25)->toDateString()
        ))->assertCreated();
    }

    public function test_senior_ticket_type_accepts_participant_above_the_minimum_age(): void
    {
        $event = Event::factory()->for(Organizer::factory())->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->senior()->create();

        $this->postJson('/api/orders', $this->payload(
            $event, $ticketType, $category, now()->subYears(70)->toDateString()
        ))->assertCreated();
    }

    public function test_senior_ticket_type_rejects_participant_below_the_minimum_age(): void
    {
        $event = Event::factory()->for(Organizer::factory())->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->senior()->create();

        $this->postJson('/api/orders', $this->payload(
            $event, $ticketType, $category, now()->subYears(30)->toDateString()
        ))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.participant_data.birthdate']);
    }

    /**
     * A idade é contada na data do evento, não na data da compra: quem completa
     * a idade mínima entre a inscrição e a prova é elegível.
     */
    public function test_age_is_measured_on_the_event_date_not_on_the_purchase_date(): void
    {
        $event = Event::factory()->for(Organizer::factory())->create([
            'date_start' => now()->addDays(30),
            'date_end' => now()->addDays(31),
        ]);
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->senior()->create();

        // Faz 60 anos daqui a 15 dias — hoje tem 59, na data do evento terá 60.
        $birthdate = now()->addDays(15)->subYears(60)->toDateString();

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $category, $birthdate))
            ->assertCreated();
    }

    public function test_only_the_senior_item_is_flagged_in_a_mixed_order(): void
    {
        $event = Event::factory()->for(Organizer::factory())->create();
        $category = Category::factory()->for($event)->create();
        $regular = TicketType::factory()->for($event)->create(['name' => 'Lote Geral']);
        $senior = TicketType::factory()->for($event)->senior()->create(['name' => 'Lote Idoso']);

        $young = now()->subYears(30)->toDateString();

        $this->postJson('/api/orders', [
            'event_id' => $event->id,
            'items' => [
                [
                    'ticket_type_id' => $regular->id,
                    'category_id' => $category->id,
                    'participant_data' => $this->participant($young, '52998224725'),
                ],
                [
                    'ticket_type_id' => $senior->id,
                    'category_id' => $category->id,
                    'participant_data' => $this->participant($young, '11144477735'),
                ],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.1.participant_data.birthdate'])
            ->assertJsonMissingValidationErrors(['items.0.participant_data.birthdate']);
    }

    public function test_super_admin_can_persist_the_senior_flag_on_a_ticket_type(): void
    {
        $admin = $this->makeSuperAdmin();
        $event = Event::factory()->for(Organizer::factory())->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->assertFalse($ticketType->requires_senior_age);

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}/ticket-types/{$ticketType->id}", [
                'requires_senior_age' => true,
                'senior_min_age' => 65,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.requires_senior_age', true)
            ->assertJsonPath('data.senior_min_age', 65);

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}/ticket-types/{$ticketType->id}", [
                'requires_senior_age' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.requires_senior_age', false);
    }

    public function test_minimum_age_is_required_when_the_senior_flag_is_on(): void
    {
        $admin = $this->makeSuperAdmin();
        $event = Event::factory()->for(Organizer::factory())->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/events/{$event->id}/ticket-types/{$ticketType->id}", [
                'requires_senior_age' => true,
                'senior_min_age' => null,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['senior_min_age']);
    }

    public function test_minimum_age_is_configurable_per_ticket_type(): void
    {
        $event = Event::factory()->for(Organizer::factory())->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->senior(65)->create();

        // 62 anos: elegível a 60+, mas não a este lote de 65+.
        $this->postJson('/api/orders', $this->payload(
            $event, $ticketType, $category, now()->subYears(62)->toDateString()
        ))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.participant_data.birthdate']);
    }
}
