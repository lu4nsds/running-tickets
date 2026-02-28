<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    /**
     * Enviar link de reset de senha por email
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->email;

        // Verificar se usuário existe
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Não encontramos um usuário com este endereço de email.',
                'errors' => [
                    'email' => ['Não encontramos um usuário com este endereço de email.']
                ]
            ], 404);
        }

        // Rate limiting - máximo 3 tentativas por hora
        $recentAttempts = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentAttempts >= 3) {
            return response()->json([
                'message' => 'Muitas tentativas. Por favor, tente novamente em uma hora.',
                'errors' => [
                    'email' => ['Muitas tentativas. Por favor, tente novamente em uma hora.']
                ]
            ], 429);
        }

        // Deletar tokens antigos do mesmo email
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Criar novo token
        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Enviar email
        Mail::to($user->email)->send(new PasswordResetMail($user, $token));

        return response()->json([
            'message' => 'Link de redefinição de senha enviado para seu email.',
        ], 200);
    }

    /**
     * Resetar a senha do usuário
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
            ],
        ], [
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        // Verificar se usuário existe
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Não encontramos um usuário com este endereço de email.',
                'errors' => [
                    'email' => ['Não encontramos um usuário com este endereço de email.']
                ]
            ], 404);
        }

        // Buscar token no banco
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Validar se token existe
        if (!$passwordReset) {
            return response()->json([
                'message' => 'Token de redefinição de senha inválido ou expirado.',
                'errors' => [
                    'token' => ['Token de redefinição de senha inválido ou expirado.']
                ]
            ], 422);
        }

        // Validar se token está correto
        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Token de redefinição de senha inválido.',
                'errors' => [
                    'token' => ['Token de redefinição de senha inválido.']
                ]
            ], 422);
        }

        // Validar se token não expirou (60 minutos)
        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Token de redefinição de senha expirado.',
                'errors' => [
                    'token' => ['Token de redefinição de senha expirado. Por favor, solicite um novo link.']
                ]
            ], 410);
        }

        // Atualizar senha do usuário
        $user->password = Hash::make($request->password);
        $user->save();

        // Deletar token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Revogar todos os tokens de acesso anteriores
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Senha redefinida com sucesso!',
        ], 200);
    }
}
