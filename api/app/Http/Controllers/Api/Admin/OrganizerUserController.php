<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrganizerRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizerUserRequest;
use App\Jobs\SendOrganizerWelcomeJob;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizerUserController extends Controller
{
    /**
     * Lista usuários do organizador
     */
    public function index(Organizer $organizer): JsonResponse
    {
        $users = $organizer->users()
            ->withPivot('role', 'created_at')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'added_at' => $user->pivot->created_at,
                ];
            });

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * Adiciona usuário ao organizador (cria se não existir)
     */
    public function store(StoreOrganizerUserRequest $request, Organizer $organizer): JsonResponse
    {
        $isNewUser = false;
        $user = User::where('email', $request->validated('email'))->first();

        if (!$user) {
            $isNewUser = true;
            $user = User::create([
                'name'     => $request->validated('name'),
                'email'    => $request->validated('email'),
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        if ($organizer->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'User already belongs to this organizer.',
            ], 422);
        }

        $organizer->users()->attach($user->id, [
            'role' => $request->validated('role'),
        ]);

        if ($isNewUser) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $token = Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email'      => $user->email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]);
            SendOrganizerWelcomeJob::dispatch($user, $organizer, $token);
        }

        return response()->json([
            'message' => 'User added to organizer successfully.',
            'data' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'role'      => $request->validated('role'),
                'added_at'  => now(),
            ],
        ], 201);
    }

    /**
     * Atualiza role do usuário no organizador
     */
    public function update(Request $request, Organizer $organizer, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(OrganizerRole::values())],
        ]);

        // Verifica se usuário pertence ao organizador
        if (!$organizer->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'User does not belong to this organizer.',
            ], 404);
        }

        // Atualiza role
        $organizer->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'role' => $validated['role'],
            ],
        ]);
    }

    /**
     * Remove usuário do organizador
     */
    public function destroy(Organizer $organizer, User $user): JsonResponse
    {
        // Verifica se usuário pertence ao organizador
        if (!$organizer->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'User does not belong to this organizer.',
            ], 404);
        }

        // Remove vínculo
        $organizer->users()->detach($user->id);

        return response()->json([
            'message' => 'User removed from organizer successfully.',
        ]);
    }
}
