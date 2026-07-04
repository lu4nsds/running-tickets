<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Papéis globais dos usuários de backoffice (reaproveita a tabela `roles`).
     * Na prática guarda apenas o papel `super_admin`.
     */
    public function up(): void
    {
        Schema::create('admin_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_roles');
    }
};
