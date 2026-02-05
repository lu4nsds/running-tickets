<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'organizer_access' => \App\Http\Middleware\EnsureOrganizerAccess::class,
            'optional_auth' => \App\Http\Middleware\OptionalAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handler para ModelNotFoundException (chamadas diretas de findOrFail)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                
                $translations = [
                    'Organizer' => 'Organizador',
                    'Event' => 'Evento',
                    'Category' => 'Categoria',
                    'TicketType' => 'Tipo de Ingresso',
                    'Order' => 'Pedido',
                    'Ticket' => 'Ingresso',
                    'User' => 'Usuário',
                ];
                
                $resourceName = $translations[$model] ?? 'Recurso';
                
                return response()->json([
                    'message' => "{$resourceName} não encontrado.",
                    'error' => "O {$resourceName} solicitado não existe ou foi removido."
                ], 404);
            }
        });
        
        // Handler para NotFoundHttpException (Route Model Binding)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                // Verificar se a exceção original era ModelNotFoundException
                $previous = $e->getPrevious();
                
                if ($previous instanceof ModelNotFoundException) {
                    $model = class_basename($previous->getModel());
                    
                    $translations = [
                        'Organizer' => 'Organizador',
                        'Event' => 'Evento',
                        'Category' => 'Categoria',
                        'TicketType' => 'Tipo de Ingresso',
                        'Order' => 'Pedido',
                        'Ticket' => 'Ingresso',
                        'User' => 'Usuário',
                    ];
                    
                    $resourceName = $translations[$model] ?? 'Recurso';
                    
                    return response()->json([
                        'message' => "{$resourceName} não encontrado.",
                        'error' => "O {$resourceName} solicitado não existe ou foi removido."
                    ], 404);
                }
                
                // Para outros tipos de 404 (rotas não encontradas)
                return response()->json([
                    'message' => 'Recurso não encontrado.',
                    'error' => 'O endpoint ou recurso solicitado não existe.'
                ], 404);
            }
        });
    })->create();
