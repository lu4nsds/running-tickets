<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove o índice único de coluna única em `admin_user_id`.
     *
     * Esse índice impedia que um mesmo usuário de backoffice pertencesse a mais
     * de um organizador, contradizendo a regra de domínio (um usuário pode ser
     * admin de um organizador e staff de outro). A unicidade do vínculo continua
     * garantida pelo índice composto (organizer_id, admin_user_id).
     */
    public function up(): void
    {
        // No MySQL, o índice único também serve de índice de apoio da FK de
        // admin_user_id, então a FK precisa sair antes de dropar o único e ser
        // recriada em seguida (gerando um índice não-único de apoio).
        Schema::table('organizer_users', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->dropUnique('organizer_users_admin_user_id_unique');
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organizer_users', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->unique('admin_user_id');
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->cascadeOnDelete();
        });
    }
};
