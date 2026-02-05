<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     * 
     * Attempts to authenticate the user if a token is present,
     * but does not require authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to authenticate via Sanctum if token is present
        if ($token = $request->bearerToken()) {
            try {
                $accessToken = PersonalAccessToken::findToken($token);
                
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                    Auth::setUser($user);
                    $request->setUserResolver(fn () => $user);
                }
            } catch (\Exception $e) {
                // If authentication fails, continue as guest
            }
        }

        return $next($request);
    }
}
