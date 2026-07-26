<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Evento ao qual a compra pertence
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            // Organizador (redundância intencional para relatórios)
            $table->foreignId('organizer_id')
                ->constrained()
                ->cascadeOnDelete();

            // Código público da compra
            $table->string('reference')->unique();

            // Usuário comprador (opcional)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Valor total
            $table->bigInteger('total_cents')->default(0);
            $table->unsignedInteger('fee_cents')->nullable();
            $table->unsignedInteger('net_amount_cents')->nullable();
            $table->string('currency', 10)->default('BRL');

            // Status da compra
            $table->string('status')->default('pending');
            // pending | paid | cancelled | refunded

            // Email e telefone do comprador (quem efetua o pagamento)
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone', 20)->nullable();

            // Informações do pagamento (gateway)
            $table->string('payment_gateway')->nullable();
            $table->string('payment_id')->nullable();

            // Modalidade de liquidação usada NESTE pagamento (snapshot congelado
            // no momento do pagamento): platform | split. Base para reconciliação
            // e para excluir pedidos split dos "Repasses Pendentes".
            $table->string('settlement_mode', 20)->nullable();
            // Comissão retida pela plataforma no split (application_fee).
            $table->unsignedInteger('application_fee_cents')->nullable();

            // Metadados adicionais
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index(['organizer_id', 'status']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
