<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Pedido ao qual o participante pertence
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Tipo de inscrição comprada
            $table->foreignId('ticket_type_id')
                ->constrained()
                ->cascadeOnDelete();

            // Categoria esportiva do atleta
            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Usuário associado (login opcional / histórico futuro)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Dados do atleta (OBRIGATÓRIO)
            $table->json('participant_data');

            // Ticket gerado após pagamento
            $table->unsignedBigInteger('ticket_id')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index('ticket_type_id');
            $table->index('category_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
