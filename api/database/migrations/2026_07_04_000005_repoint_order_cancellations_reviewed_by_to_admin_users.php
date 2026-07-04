<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `reviewed_by` identifica o super admin que avaliou a solicitação de
     * cancelamento — agora um usuário de backoffice. Repontar a FK para
     * `admin_users`. `requested_by` (comprador) permanece em `users`.
     */
    public function up(): void
    {
        Schema::table('order_cancellations', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });

        foreach (DB::table('order_cancellations')->whereNotNull('reviewed_by')->get() as $row) {
            $email = DB::table('users')->where('id', $row->reviewed_by)->value('email');
            $adminUserId = $email ? DB::table('admin_users')->where('email', $email)->value('id') : null;

            DB::table('order_cancellations')->where('id', $row->id)->update(['reviewed_by' => $adminUserId]);
        }

        Schema::table('order_cancellations', function (Blueprint $table) {
            $table->foreign('reviewed_by')->references('id')->on('admin_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_cancellations', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });

        foreach (DB::table('order_cancellations')->whereNotNull('reviewed_by')->get() as $row) {
            $email = DB::table('admin_users')->where('id', $row->reviewed_by)->value('email');
            $userId = $email ? DB::table('users')->where('email', $email)->value('id') : null;

            DB::table('order_cancellations')->where('id', $row->id)->update(['reviewed_by' => $userId]);
        }

        Schema::table('order_cancellations', function (Blueprint $table) {
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
