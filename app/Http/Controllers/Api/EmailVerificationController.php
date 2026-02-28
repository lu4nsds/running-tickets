<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    /**
     * Reenviar email de verificação
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email já verificado.',
            ], 200);
        }

        Mail::to($user->email)->send(new VerifyEmailMail($user));

        return response()->json([
            'message' => 'Email de verificação reenviado com sucesso.',
        ], 200);
    }

    /**
     * Verificar email do usuário
     */
    public function verify(Request $request, $id, $hash)
    {
        // Validar assinatura da URL
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Link de verificação inválido ou expirado.',
                'errors' => [
                    'email' => ['Link de verificação inválido ou expirado.']
                ]
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado.',
                'errors' => [
                    'user' => ['Usuário não encontrado.']
                ]
            ], 404);
        }

        // Validar hash do email
        if (!hash_equals((string) $hash, sha1($user->email))) {
            return response()->json([
                'message' => 'Link de verificação inválido.',
                'errors' => [
                    'email' => ['Link de verificação inválido.']
                ]
            ], 422);
        }

        // Verificar se já não foi verificado
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email já verificado anteriormente.',
                'already_verified' => true,
            ], 200);
        }

        // Marcar como verificado
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verificado com sucesso!',
            'verified' => true,
        ], 200);
    }

    /**
     * Verificar se o usuário já verificou o email
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'verified' => $user->hasVerifiedEmail(),
        ], 200);
    }
}
