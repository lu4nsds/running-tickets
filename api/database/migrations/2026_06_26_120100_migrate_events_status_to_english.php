<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migra os valores de status do evento de português para inglês,
     * alinhando com OrganizerStatus/TicketStatus (TD-011).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('status')->default('inactive')->change();
        });

        DB::table('events')->where('status', 'ativo')->update(['status' => 'active']);
        DB::table('events')->where('status', 'inativo')->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('events')->where('status', 'active')->update(['status' => 'ativo']);
        DB::table('events')->where('status', 'inactive')->update(['status' => 'inativo']);
        // Eventos "finished" não possuem equivalente legado; permanecem como estão.

        Schema::table('events', function (Blueprint $table) {
            $table->string('status')->default('inativo')->change();
        });
    }
};
