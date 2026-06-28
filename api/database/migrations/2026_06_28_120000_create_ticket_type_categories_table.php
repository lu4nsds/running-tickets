<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_type_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            // Evita vínculo duplicado entre o mesmo lote e categoria
            $table->unique(['ticket_type_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_type_categories');
    }
};
