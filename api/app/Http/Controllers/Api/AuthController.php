<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\VerifyEmailMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Atribuir role padrão 'user'
        $user->assignRole(UserRole::USER->value);

        // Vincular pedidos feitos como convidado com o mesmo e-mail
        Order::where('buyer_email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        // Enviar email de verificação
        Mail::to($user->email)->send(new VerifyEmailMail($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'email_verification_sent' => true,
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'remember' => 'boolean',
            'source'   => ['nullable', 'string', Rule::in(['admin', 'client'])],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        if ($request->source === 'client') {
            $expiresAt = now()->addDays(30);
        } elseif ($request->boolean('remember')) {
            $expiresAt = now()->addDays(7);
        } else {
            $expiresAt = now()->addHours(8);
        }

        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user'         => $user->load('roles', 'organizers'),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Dados do usuário autenticado
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'organizers'));
    }

    /**
     * Redireciona para o Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Callback do Google OAuth — encontra ou cria usuário e emite token Sanctum
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            \Log::error('Google OAuth callback error', ['message' => $e->getMessage()]);
            return redirect(config('app.client_url') . '/auth/callback?error=oauth_failed');
        }

        // Encontra por google_id, e-mail ou cria novo usuário
        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Vincula google_id ao usuário existente
                $user->update(['google_id' => $googleUser->getId()]);
            } else {
                // Cria novo usuário sem senha
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password'  => null,
                ]);

                $user->assignRole(UserRole::USER->value);

                // Vincula pedidos de guest com o mesmo e-mail
                Order::where('buyer_email', $user->email)
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->id]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $frontendUrl = config('app.client_url');
        return redirect("{$frontendUrl}/auth/callback?token={$token}");
    }
}
