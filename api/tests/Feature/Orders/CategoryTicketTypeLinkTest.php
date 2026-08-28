<?php

namespace Tests\Feature\Orders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTicketTypeLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Monta um payload de pedido de 1 participante.
     */
    private function payload(Event $event, TicketType $ticketType, ?Category $category): array
    {
        $item = [
            'ticket_type_id' => $ticketType->id,
            'participant_data' => [
                'name' => 'Comprador Teste',
                'email' => 'comprador@test.com',
                'cpf' => '52998224725',
                'phone' => '11999990000',
                'birthdate' => '1990-01-01',
                'gender' => 'M',
            ],
        ];

        if ($category) {
            $item['category_id'] = $category->id;
        }

        return [
            'event_id' => $event->id,
            'items' => [$item],
        ];
    }

    public function test_order_accepts_category_linked_to_ticket_type(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $linked = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $ticketType->categories()->sync([$linked->id]);

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $linked))
            ->assertCreated();
    }

    public function test_order_rejects_category_not_linked_when_ticket_type_has_links(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $linked = Category::factory()->for($event)->create();
        $unlinked = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $ticketType->categories()->sync([$linked->id]);

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $unlinked))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.category_id']);
    }

    public function test_order_accepts_any_active_category_when_ticket_type_has_no_links(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        $category = Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();
        // Sem vínculos → fallback libera qualquer categoria ativa do evento.

        $this->postJson('/api/orders', $this->payload($event, $ticketType, $category))
            ->assertCreated();
    }

    public function test_order_requires_category_id(): void
    {
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->for($organizer)->create();
        Category::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();

        $this->postJson('/api/orders', $this->payload($event, $ticketType, null))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.category_id']);
    }
}
