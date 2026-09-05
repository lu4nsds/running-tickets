<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Marca o envio do convite de feedback. O comprador é o
            // destinatário, então a deduplicação é por pedido — um pedido com
            // vários ingressos gera um único envio.
            $table->timestamp('feedback_sent_at')->nullable()->after('paid_at');
            $table->index('feedback_sent_at');
        });

        // Backfill defensivo: pedidos com ingressos já validados entram como
        // "notificados". Sem isso, o primeiro run do agendador dispararia o
        // convite retroativamente para todo o histórico de eventos.
        DB::table('orders')
            ->whereNull('feedback_sent_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tickets')
                    ->join('order_items', 'order_items.id', '=', 'tickets.order_item_id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('tickets.status', 'used');
            })
            ->update(['feedback_sent_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['feedback_sent_at']);
            $table->dropColumn('feedback_sent_at');
        });
    }
};
