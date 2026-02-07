<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Segurança: só roda em ambiente local
        if (!app()->environment('local')) {
            return;
        }

        $organizer = Organizer::where('email', 'organizador@dev.local')->first();
        
        if (!$organizer) {
            return;
        }

        $events = Event::where('organizer_id', $organizer->id)->get();

        foreach ($events as $event) {
            $ticketTypes = TicketType::where('event_id', $event->id)->get();
            $categories = Category::where('event_id', $event->id)->get();

            if ($ticketTypes->isEmpty()) {
                continue;
            }

            // Criar 30 pedidos pagos para cada evento
            for ($i = 1; $i <= 30; $i++) {
                $ticketType = $ticketTypes->random();
                $category = $categories->random();
                $quantity = rand(1, 3); // 1 a 3 ingressos por pedido

                $totalCents = $ticketType->price_cents * $quantity;

                $order = Order::create([
                    'event_id' => $event->id,
                    'organizer_id' => $organizer->id,
                    'reference' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => null, // Guest
                    'total_cents' => $totalCents,
                    'currency' => Currency::BRL->value,
                    'status' => OrderStatus::PAID->value,
                    'payment_gateway' => PaymentGateway::MERCADOPAGO->value,
                    'payment_id' => 'MP-' . rand(100000, 999999),
                    'metadata' => [
                        'ip' => '192.168.1.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0',
                    ],
                    'created_at' => now()->subDays(rand(0, 7)),
                    'updated_at' => now()->subDays(rand(0, 7)),
                ]);

                // Criar order items e tickets
                for ($j = 0; $j < $quantity; $j++) {
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'ticket_type_id' => $ticketType->id,
                        'category_id' => $category->id,
                        'user_id' => null,
                        'participant_data' => [
                            'name' => 'Participante ' . fake()->name(),
                            'email' => fake()->email(),
                            'cpf' => fake()->numerify('###########'),
                            'birth_date' => fake()->date('Y-m-d', '-25 years'),
                            'gender' => $category->gender,
                        ],
                    ]);

                    // Criar ticket
                    Ticket::create([
                        'order_item_id' => $orderItem->id,
                        'code' => Str::uuid(),
                        'qr_path' => null,
                        'status' => TicketStatus::ACTIVE->value,
                        'issued_at' => now(),
                    ]);
                }
            }

            // Criar 10 pedidos pendentes
            for ($i = 1; $i <= 10; $i++) {
                $ticketType = $ticketTypes->random();
                $category = $categories->random();
                $quantity = rand(1, 2);

                $totalCents = $ticketType->price_cents * $quantity;

                $order = Order::create([
                    'event_id' => $event->id,
                    'organizer_id' => $organizer->id,
                    'reference' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => null,
                    'total_cents' => $totalCents,
                    'currency' => Currency::BRL->value,
                    'status' => OrderStatus::PENDING->value,
                    'payment_gateway' => null,
                    'payment_id' => null,
                    'metadata' => [
                        'ip' => '192.168.1.' . rand(1, 255),
                    ],
                    'created_at' => now()->subDays(rand(0, 3)),
                    'updated_at' => now()->subDays(rand(0, 3)),
                ]);

                // Criar order items (sem tickets pois não foi pago)
                for ($j = 0; $j < $quantity; $j++) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'ticket_type_id' => $ticketType->id,
                        'category_id' => $category->id,
                        'user_id' => null,
                        'participant_data' => [
                            'name' => 'Participante ' . fake()->name(),
                            'email' => fake()->email(),
                            'cpf' => fake()->numerify('###########'),
                            'birth_date' => fake()->date('Y-m-d', '-25 years'),
                            'gender' => $category->gender,
                        ],
                    ]);
                }
            }
        }

        $this->command->info('✅ Pedidos criados com sucesso!');
    }
}
