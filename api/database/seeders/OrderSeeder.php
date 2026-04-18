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

        $events = Event::with('organizer')->get();

        if ($events->isEmpty()) {
            $this->command->error('❌ Nenhum evento encontrado! Execute EventSeeder primeiro.');
            return;
        }

        $totalOrders = 0;

        foreach ($events as $event) {
            $ticketTypes = TicketType::where('event_id', $event->id)->get();
            $categories = Category::where('event_id', $event->id)->get();

            if ($ticketTypes->isEmpty()) {
                continue;
            }

            // Criar entre 40-80 pedidos pagos para cada evento (distribuídos nos últimos 30 dias)
            $numPaidOrders = rand(40, 80);
            
            for ($i = 1; $i <= $numPaidOrders; $i++) {
                $ticketType = $ticketTypes->random();
                $category = $categories->random();
                $quantity = rand(1, 3); // 1 a 3 ingressos por pedido

                $totalCents = $ticketType->price_cents * $quantity;
                
                // Distribuir pedidos nos últimos 30 dias com pico nos primeiros 7 dias
                $daysAgo = $this->getRandomDaysAgo();
                $createdAt = now()->subDays($daysAgo);

                // Taxa do Mercado Pago: entre 2.49% e 3.99% dependendo do método
                $feeRate = rand(249, 399) / 10000;
                $feeCents = (int) round($totalCents * $feeRate);
                $netAmountCents = $totalCents - $feeCents;

                $order = Order::create([
                    'event_id'         => $event->id,
                    'organizer_id'     => $event->organizer_id,
                    'reference'        => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id'          => null,
                    'total_cents'      => $totalCents,
                    'fee_cents'        => $feeCents,
                    'net_amount_cents' => $netAmountCents,
                    'currency'         => Currency::BRL->value,
                    'status'           => OrderStatus::PAID->value,
                    'payment_gateway'  => PaymentGateway::MERCADOPAGO->value,
                    'payment_id'       => 'MP-' . rand(100000, 999999),
                    'metadata'         => [
                        'ip'         => '192.168.1.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0',
                    ],
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt,
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
                        'issued_at' => $createdAt,
                    ]);
                }
                
                $totalOrders++;
            }

            // Criar entre 5-15 pedidos pendentes
            $numPendingOrders = rand(5, 15);
            
            for ($i = 1; $i <= $numPendingOrders; $i++) {
                $ticketType = $ticketTypes->random();
                $category = $categories->random();
                $quantity = rand(1, 2);

                $totalCents = $ticketType->price_cents * $quantity;
                $createdAt = now()->subDays(rand(0, 5));

                $order = Order::create([
                    'event_id' => $event->id,
                    'organizer_id' => $event->organizer_id,
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
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
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
                
                $totalOrders++;
            }
            
            // Criar alguns pedidos cancelados (10-20% dos pagos)
            $numCancelledOrders = rand(5, 10);
            
            for ($i = 1; $i <= $numCancelledOrders; $i++) {
                $ticketType = $ticketTypes->random();
                $category = $categories->random();
                $quantity = rand(1, 2);

                $totalCents = $ticketType->price_cents * $quantity;
                $createdAt = now()->subDays(rand(1, 20));

                $order = Order::create([
                    'event_id' => $event->id,
                    'organizer_id' => $event->organizer_id,
                    'reference' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => null,
                    'total_cents' => $totalCents,
                    'currency' => Currency::BRL->value,
                    'status' => OrderStatus::CANCELLED->value,
                    'payment_gateway' => null,
                    'payment_id' => null,
                    'metadata' => [
                        'ip' => '192.168.1.' . rand(1, 255),
                        'cancelled_reason' => 'Solicitação do cliente',
                    ],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Criar order items
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
                
                $totalOrders++;
            }
        }

        $this->command->info("✅ {$totalOrders} pedidos criados com sucesso!");
    }
    
    /**
     * Gera dias aleatórios com distribuição realista (mais vendas recentes)
     */
    private function getRandomDaysAgo(): int
    {
        $rand = rand(1, 100);
        
        // 40% nos últimos 7 dias
        if ($rand <= 40) {
            return rand(0, 7);
        }
        
        // 30% entre 8-15 dias
        if ($rand <= 70) {
            return rand(8, 15);
        }
        
        // 30% entre 16-30 dias
        return rand(16, 30);
    }
}
