<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Evento ao qual a categoria pertence
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            // Nome da categoria (ex: Masculino 30-39)
            $table->string('name');

            // Sexo permitido
            $table->enum('gender', ['M', 'F', 'X'])->nullable();
            // M = masculino | F = feminino | X = misto / não especificado

            // Faixa etária
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();

            // Controle
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Evita duplicar categorias no mesmo evento
            $table->unique(['event_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
