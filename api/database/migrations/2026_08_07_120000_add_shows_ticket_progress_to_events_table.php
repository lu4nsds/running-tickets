<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Exibe no portal a barra de vendas e a contagem de vagas restantes
            // de cada lote. Escassez é informação sensível para o organizador,
            // então a exposição é opt-in.
            $table->boolean('shows_ticket_progress')->default(false)->after('allows_late_refund_request');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('shows_ticket_progress');
        });
    }
};
