<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Momento do check-in. Antes disso a data de validação era inferida
            // de `updated_at`, que qualquer outra escrita no ticket corrompia.
            // É também a âncora do envio de feedback pós-evento.
            $table->timestamp('validated_at')->nullable()->after('issued_at');
            $table->index('validated_at');
        });

        // Backfill: para tickets já utilizados, `updated_at` é a melhor
        // aproximação disponível do check-in.
        DB::table('tickets')
            ->where('status', 'used')
            ->whereNull('validated_at')
            ->update(['validated_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['validated_at']);
            $table->dropColumn('validated_at');
        });
    }
};
