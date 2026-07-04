<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repontar `organizer_users` da tabela `users` para `admin_users`.
     * O vínculo organizador↔usuário passa a ser feito com usuários de backoffice.
     */
    public function up(): void
    {
        Schema::table('organizer_users', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable()->after('organizer_id');
        });

        // Backfill: mapeia user_id -> admin_user_id via e-mail
        foreach (DB::table('organizer_users')->get() as $row) {
            $email = DB::table('users')->where('id', $row->user_id)->value('email');
            $adminUserId = $email ? DB::table('admin_users')->where('email', $email)->value('id') : null;

            if ($adminUserId) {
                DB::table('organizer_users')->where('id', $row->id)->update(['admin_user_id' => $adminUserId]);
            }
        }

        // Remove as duas FKs primeiro: no MySQL o índice único composto
        // (organizer_id, user_id) também é usado como índice de apoio da FK de
        // organizer_id, então ambos precisam sair antes dos índices.
        Schema::table('organizer_users', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique('organizer_users_user_id_unique');
            $table->dropUnique('organizer_users_organizer_id_user_id_unique');
            $table->dropColumn('user_id');
        });

        Schema::table('organizer_users', function (Blueprint $table) {
            $table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->cascadeOnDelete();
            $table->unique('admin_user_id');
            $table->unique(['organizer_id', 'admin_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('organizer_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('organizer_id');
        });

        foreach (DB::table('organizer_users')->get() as $row) {
            $email = DB::table('admin_users')->where('id', $row->admin_user_id)->value('email');
            $userId = $email ? DB::table('users')->where('email', $email)->value('id') : null;

            if ($userId) {
                DB::table('organizer_users')->where('id', $row->id)->update(['user_id' => $userId]);
            }
        }

        Schema::table('organizer_users', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
            $table->dropForeign(['admin_user_id']);
            $table->dropUnique('organizer_users_admin_user_id_unique');
            $table->dropUnique('organizer_users_organizer_id_admin_user_id_unique');
            $table->dropColumn('admin_user_id');
        });

        Schema::table('organizer_users', function (Blueprint $table) {
            $table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('user_id');
            $table->unique(['organizer_id', 'user_id']);
        });
    }
};
