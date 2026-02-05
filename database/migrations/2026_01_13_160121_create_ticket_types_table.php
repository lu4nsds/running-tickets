<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();

            // Evento ao qual esse ticket pertence
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            // Nome do produto
            $table->string('name');

            // Descrição opcional
            $table->text('description')->nullable();

            // Preço em centavos (evita problemas de arredondamento)
            $table->bigInteger('price_cents')->default(0);

            // Moeda
            $table->string('currency', 10)->default('BRL');

            // Limite de vendas (opcional)
            $table->integer('quota')->nullable();

            // Período de venda
            $table->dateTime('start_sale')->nullable();
            $table->dateTime('end_sale')->nullable();

            // Atributos flexíveis (kit, camisa, etc.)
            $table->json('attributes')->nullable();

            // Controle
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Evita duplicar nomes no mesmo evento
            $table->unique(['event_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
