<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizerAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Não autenticado.'
            ], 401);
        }

        // Super admin tem acesso a tudo
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Verificar se tem organizer_id na rota
        $organizer = $request->route('organizer') ?? $request->input('organizer_id');
        
        if ($organizer) {
            // Se $organizer for um Model (Route Model Binding), extrair o ID
            // Se for um ID, usar direto
            $organizerId = $organizer instanceof \App\Models\Organizer 
                ? $organizer->id 
                : $organizer;
            
            if (!$user->canAccessOrganizer($organizerId)) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar este organizador.'
                ], 403);
            }
        } else {
            $organizerId = null;
        }

        // Verificar se tem event_id e se o event pertence a um organizer do user
        $event = $request->route('event');
        
        if ($event) {
            // Se $event for um Model (Route Model Binding), já temos o objeto
            // Se for um ID, precisamos buscar
            if (!$event instanceof \App\Models\Event) {
                $event = \App\Models\Event::find($event);
            }
            
            if ($event && !$user->canAccessOrganizer($event->organizer_id)) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar este evento.'
                ], 403);
            }
        }

        // Se não tem organizer_id nem event_id na rota, verificar se tem pelo menos 1 organizer
        if (!$organizerId && !$event && $user->organizers()->count() === 0) {
            return response()->json([
                'message' => 'Você não está vinculado a nenhum organizador.'
            ], 403);
        }

        return $next($request);
    }
}
