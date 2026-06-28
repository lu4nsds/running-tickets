<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            // Define se este lote oferece seleção de tamanho de camiseta no checkout.
            // Novos lotes vêm desativados (opt-in).
            $table->boolean('allows_shirt_size')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn('allows_shirt_size');
        });
    }
};
