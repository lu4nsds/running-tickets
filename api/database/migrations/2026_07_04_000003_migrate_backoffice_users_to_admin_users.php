<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Copia (não move) para `admin_users` todos os usuários de backoffice
     * existentes: super admins (via user_roles) e admins/staff de organizadores
     * (via organizer_users). A linha original em `users` é preservada, pois a
     * mesma pessoa pode também ser compradora do portal.
     *
     * Numa base nova (migrate:fresh) não há dados: os seeders populam
     * `admin_users` diretamente após as migrations.
     */
    public function up(): void
    {
        $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

        $superAdminUserIds = $superAdminRoleId
            ? DB::table('user_roles')->where('role_id', $superAdminRoleId)->pluck('user_id')->all()
            : [];

        $organizerUserIds = DB::table('organizer_users')->pluck('user_id')->all();

        $backofficeUserIds = array_values(array_unique(array_merge($superAdminUserIds, $organizerUserIds)));

        if (empty($backofficeUserIds)) {
            return;
        }

        $users = DB::table('users')->whereIn('id', $backofficeUserIds)->get();

        foreach ($users as $user) {
            DB::table('admin_users')->updateOrInsert(
                ['email' => $user->email],
                [
                    'name' => $user->name,
                    'password' => $user->password,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => $user->updated_at ?? now(),
                ]
            );
        }

        // Replica o papel super_admin para os admin_users correspondentes
        if ($superAdminRoleId) {
            $superAdminEmails = DB::table('users')->whereIn('id', $superAdminUserIds)->pluck('email');

            foreach ($superAdminEmails as $email) {
                $adminUserId = DB::table('admin_users')->where('email', $email)->value('id');

                if ($adminUserId) {
                    DB::table('admin_user_roles')->updateOrInsert(
                        ['admin_user_id' => $adminUserId, 'role_id' => $superAdminRoleId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Remove os admin_users que espelham usuários de backoffice existentes.
        $emails = DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('user_id')->from('organizer_users');
            })
            ->orWhereIn('id', function ($query) {
                $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');
                $query->select('user_id')->from('user_roles')->where('role_id', $superAdminRoleId);
            })
            ->pluck('email');

        DB::table('admin_users')->whereIn('email', $emails)->delete();
    }
};
