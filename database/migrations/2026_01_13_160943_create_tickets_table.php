<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Participante ao qual o ticket pertence
            $table->foreignId('order_item_id')
                ->constrained()
                ->cascadeOnDelete();

            // Código único do ticket (usado em QR Code)
            $table->uuid('code')->unique();

            // Caminho do QR Code (arquivo ou URL)
            $table->string('qr_path')->nullable();

            // Status do ticket
            $table->string('status')->default('active');
            // active | used | cancelled | refunded

            // Quando o ticket foi emitido
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            // Índice para validação rápida
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
