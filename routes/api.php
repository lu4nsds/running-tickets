<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\TicketTypeController as AdminTicketTypeController;
use App\Http\Controllers\Api\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Api\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Api\Organizer\PayoutController;
use App\Http\Controllers\Api\Organizer\CategoryController as OrganizerCategoryController;
use App\Http\Controllers\Api\Organizer\TicketTypeController as OrganizerTicketTypeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\MercadoPagoWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminDashboardController;

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Rotas públicas (sem autenticação)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Eventos públicos
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);

// Pedidos - autenticação opcional (funciona com ou sem token)
Route::post('/orders', [OrderController::class, 'store'])->middleware('optional_auth');

// Webhook do Mercado Pago (público)
Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])->name('webhooks.mercadopago');

// Rotas protegidas (requer autenticação)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Pedidos do usuário autenticado
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // Tickets do usuário autenticado
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{code}', [TicketController::class, 'show']);
    Route::get('/tickets/{code}/qr', [TicketController::class, 'downloadQr']);
    
    // Validação de tickets (requer acesso ao organizador do evento)
    Route::post('/tickets/{code}/validate', [TicketController::class, 'validate']);
    
    // Super Admin
    Route::middleware('super_admin')->prefix('admin')->group(function () {
        // Dashboard administrativo
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/organizers/{organizer}/dashboard', [AdminDashboardController::class, 'organizerDashboard']);
        
        // CRUD de Organizadores
        Route::apiResource('organizers', AdminOrganizerController::class);
        
        // Gerenciamento de usuários do organizador
        Route::prefix('organizers/{organizer}')->group(function () {
            Route::get('/users', [\App\Http\Controllers\Api\Admin\OrganizerUserController::class, 'index']);
            Route::post('/users', [\App\Http\Controllers\Api\Admin\OrganizerUserController::class, 'store']);
            Route::patch('/users/{user}', [\App\Http\Controllers\Api\Admin\OrganizerUserController::class, 'update']);
            Route::delete('/users/{user}', [\App\Http\Controllers\Api\Admin\OrganizerUserController::class, 'destroy']);
        });
        
        // CRUD de Eventos
        Route::apiResource('events', AdminEventController::class);
        
        // Configuração de modo de pagamento (admin only)
        Route::put('/events/{event}/payout-mode', [AdminPayoutController::class, 'setPayoutMode']);
        
        // CRUD de Categorias (nested em eventos)
        Route::apiResource('events.categories', AdminCategoryController::class);
        
        // CRUD de Tipos de Ingresso (nested em eventos)
        Route::apiResource('events.ticket-types', AdminTicketTypeController::class);
    });
    
    // Organizers (contexto específico)
    Route::middleware('organizer_access')->prefix('organizer')->group(function () {
        // Dashboard do organizador
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/events/{event}/dashboard', [DashboardController::class, 'eventDashboard']);
        
        // Visualizar eventos (read-only)
        Route::get('/events', [OrganizerEventController::class, 'index']);
        Route::get('/events/{event}', [OrganizerEventController::class, 'show']);
        
        // Configurações de pagamento
        Route::get('/payment-settings', [PayoutController::class, 'index']);
        Route::get('/events/{event}/payout', [PayoutController::class, 'show']);
        Route::put('/events/{event}/payout', [PayoutController::class, 'update']);
        Route::post('/events/{event}/payout/validate', [PayoutController::class, 'validateCredentials']);
        
        // Visualizar categorias (read-only)
        Route::get('/events/{event}/categories', [OrganizerCategoryController::class, 'index']);
        Route::get('/events/{event}/categories/{category}', [OrganizerCategoryController::class, 'show']);
        
        // Visualizar tipos de ingresso (read-only)
        Route::get('/events/{event}/ticket-types', [OrganizerTicketTypeController::class, 'index']);
        Route::get('/events/{event}/ticket-types/{ticket_type}', [OrganizerTicketTypeController::class, 'show']);
    });
});

