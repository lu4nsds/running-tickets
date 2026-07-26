<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_accounts', function (Blueprint $table) {
            $table->id();

            // Organizador dono da conta (a conexão é por organizador).
            $table->foreignId('organizer_id')
                ->constrained()
                ->cascadeOnDelete();

            // Gateway do provedor. Nome genérico já pensando no multi-gateway.
            $table->string('gateway', 30)->default('mercadopago');

            // Identificador da conta no provedor (mp_user_id / collector_id).
            $table->string('provider_account_id')->nullable();

            // Credenciais OAuth — criptografadas em repouso via cast no model.
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('public_key')->nullable();

            // Ciclo de vida do token OAuth.
            $table->dateTime('expires_at')->nullable();
            $table->string('scopes')->nullable();

            // connected | expired | revoked
            $table->string('status', 20)->default('connected');
            $table->dateTime('connected_at')->nullable();

            $table->timestamps();

            // Uma conexão por (organizador, gateway).
            $table->unique(['organizer_id', 'gateway']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_accounts');
    }
};
