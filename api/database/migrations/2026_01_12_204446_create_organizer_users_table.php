<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_users', function (Blueprint $table) {
            $table->id();

            // Organizador ao qual o usuário pertence
            $table->foreignId('organizer_id')
                ->constrained()
                ->cascadeOnDelete();

            // Usuário do sistema
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Papel do usuário dentro do organizador
            $table->string('role')->default('admin');
            // admin | staff

            $table->timestamps();

            // Um usuário não pode ter dois vínculos iguais
            $table->unique(['organizer_id', 'user_id']);
            
            // Um usuário só pode pertencer a 1 organizador
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_users');
    }
};
