<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            // Define se este lote é exclusivo para idosos. Com a flag ligada, a compra
            // é bloqueada para participantes que não tiverem `senior_min_age` anos
            // completos na data do evento. Novos lotes vêm desativados (opt-in).
            $table->boolean('requires_senior_age')->default(false)->after('allows_shirt_size');

            // Idade mínima exigida. O default 60 segue o Estatuto do Idoso
            // (Lei 10.741/2003, art. 1º); o organizador pode elevar conforme o
            // regulamento da prova. Só é lido quando a flag acima está ligada.
            $table->unsignedTinyInteger('senior_min_age')->default(60)->after('requires_senior_age');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn(['requires_senior_age', 'senior_min_age']);
        });
    }
};
