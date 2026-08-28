<?php

namespace Tests\Feature\Orders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShirtSizeOptionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Event $event, TicketType $ticketType, Category $category, ?string $shirtSize): array
    {
        $participant = [
            'name' => 'Comprador Teste',
            'email' => 'comprador@test.com',
            'cpf' => '52998224725',
            'phone' => '11999990000',
            'birthdate' => '1990-01-01',
            'gender' => 'M',
        ];

        if ($shirtSize !== null) {
            $participant['shirt_size'] = $shirtSize;
        }

        return [
            'event_id' => $event->id,
            'items' => [[
                'ticket_type_id' => $ticketType->id,
                'category_id' => $category->id,
                'participant_data' => $participant,
            ]],
        ];
    }

    public function test_order_accepts_shirt_size_when_ticket_type_allows_it(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create(['allows_shirt_size' => true]);

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $category, 'M'))
            ->assertCreated();
    }

    public function test_order_rejects_shirt_size_when_ticket_type_does_not_allow_it(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create(['allows_shirt_size' => false]);

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $category, 'M'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.participant_data.shirt_size']);
    }

    public function test_order_without_shirt_size_is_accepted_when_not_allowed(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create(['allows_shirt_size' => false]);

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $category, null))
            ->assertCreated();
    }
}
