<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Dispensa a janela de 7 dias na solicitação de cancelamento/estorno:
            // com a flag ligada, o comprador pode solicitar até o início do evento.
            // Eventos existentes mantêm o comportamento atual (opt-in).
            $table->boolean('allows_late_refund_request')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('allows_late_refund_request');
        });
    }
};
