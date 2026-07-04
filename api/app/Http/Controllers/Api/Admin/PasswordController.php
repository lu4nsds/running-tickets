<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Reset e ativação de senha para usuários de backoffice (`admin_users`).
 * Espelha o fluxo do PasswordResetController do portal, mas resolve `AdminUser`.
 */
class PasswordController extends Controller
{
    /**
     * Envia link de redefinição de senha (app admin)
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->email;
        $adminUser = AdminUser::where('email', $email)->first();

        if (! $adminUser) {
            return response()->json([
                'message' => 'Não encontramos um usuário com este endereço de email.',
                'errors' => ['email' => ['Não encontramos um usuário com este endereço de email.']],
            ], 404);
        }

        $recentAttempts = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentAttempts >= 3) {
            return response()->json([
                'message' => 'Muitas tentativas. Por favor, tente novamente em uma hora.',
                'errors' => ['email' => ['Muitas tentativas. Por favor, tente novamente em uma hora.']],
            ], 429);
        }

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        Mail::to($adminUser->email)->send(new PasswordResetMail($adminUser, $token, 'admin'));

        return response()->json([
            'message' => 'Link de redefinição de senha enviado para seu email.',
        ], 200);
    }

    /**
     * Redefine a senha do usuário de backoffice
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        return $this->applyNewPassword($request, expiryMinutes: 60, invalidMessage: 'Token de redefinição de senha');
    }

    /**
     * Ativa a conta de um usuário de backoffice criado por convite (expiração de 48h)
     */
    public function activate(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        return $this->applyNewPassword($request, expiryMinutes: 2880, invalidMessage: 'Link de ativação');
    }

    /**
     * Valida o token e define a nova senha do AdminUser.
     */
    private function applyNewPassword(Request $request, int $expiryMinutes, string $invalidMessage)
    {
        $adminUser = AdminUser::where('email', $request->email)->first();

        if (! $adminUser) {
            return response()->json([
                'message' => "{$invalidMessage} inválido.",
                'errors' => ['token' => ["{$invalidMessage} inválido."]],
            ], 422);
        }

        $passwordReset = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $passwordReset || ! Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => "{$invalidMessage} inválido ou expirado.",
                'errors' => ['token' => ["{$invalidMessage} inválido ou expirado."]],
            ], 422);
        }

        if (now()->diffInMinutes($passwordReset->created_at) > $expiryMinutes) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'message' => "{$invalidMessage} expirado.",
                'errors' => ['token' => ["{$invalidMessage} expirado. Solicite um novo link."]],
            ], 410);
        }

        $adminUser->password = Hash::make($request->password);
        $adminUser->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $adminUser->tokens()->delete();

        return response()->json([
            'message' => 'Senha definida com sucesso! Faça login para acessar o sistema.',
        ], 200);
    }
}
