<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_payout_settings', function (Blueprint $table) {
            $table->id();

            // Evento ao qual essa configuração pertence
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            // Método geral de recebimento
            $table->string('method');
            // pix | bank_account | gateway

            // Modo de repasse
            $table->enum('payout_mode', ['direct', 'platform'])
                  ->default('direct')
                  ->comment('direct: organizer MP account, platform: platform MP account with manual repasse');

            // Provedor específico
            $table->string('provider')->nullable();
            // pix | banco_do_brasil | stripe | pagarme

            // Dados específicos do método (JSON)
            $table->json('details');

            // Apenas uma configuração ativa por evento
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Garante uma config ativa por evento
            $table->unique(['event_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_payout_settings');
    }
};
