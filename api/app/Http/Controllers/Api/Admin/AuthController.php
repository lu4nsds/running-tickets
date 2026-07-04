<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticação exclusiva do backoffice (app admin), sobre a tabela `admin_users`
 * e o guard `admin`. Não há registro público nem Google OAuth.
 */
class AuthController extends Controller
{
    /**
     * Login do backoffice
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean',
        ]);

        $adminUser = AdminUser::where('email', $request->email)->first();

        if (! $adminUser || ! $adminUser->password || ! Hash::check($request->password, $adminUser->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $expiresAt = $request->boolean('remember')
            ? now()->addDays(7)
            : now()->addHours(8);

        $token = $adminUser->createToken('admin_auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => $adminUser->load('roles', 'organizers'),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout do backoffice
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Dados do usuário de backoffice autenticado
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'organizers'));
    }
}
