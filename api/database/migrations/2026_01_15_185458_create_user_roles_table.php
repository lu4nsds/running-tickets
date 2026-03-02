<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            
            // Usuário do sistema
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            
            // Papel global atribuído
            $table->foreignId('role_id')
                ->constrained()
                ->cascadeOnDelete();
            
            $table->timestamps();
            
            // Um usuário não pode ter o mesmo papel duas vezes
            $table->unique(['user_id', 'role_id']);
            
            // Índices para queries
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
