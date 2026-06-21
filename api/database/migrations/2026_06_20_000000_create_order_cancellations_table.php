<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();

            // Pedido alvo da solicitação
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Quem solicitou (null = pedido de convidado/guest)
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Motivo informado pelo comprador
            $table->text('reason');

            // Status da solicitação
            $table->string('status', 20)->default('pending');
            // pending | approved | rejected

            // Quem avaliou (super admin)
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            // ID do estorno no Mercado Pago (preenchido na aprovação)
            $table->string('refund_id')->nullable();

            // Observações da avaliação (ex.: motivo da rejeição)
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cancellations');
    }
};
