<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Dono do evento (organizador)
            $table->foreignId('organizer_id')
                ->constrained()
                ->cascadeOnDelete();

            // Dados públicos do evento
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->string('state', 2);
            $table->string('city');
            $table->string('venue');

            $table->dateTime('date_start');
            $table->dateTime('date_end')->nullable();

            $table->integer('max_participants')->nullable();

            $table->string('banner_url')->nullable();
            $table->string('status')->default('inativo');

            // Dados flexíveis (regulamento, links, mapas, etc.)
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
