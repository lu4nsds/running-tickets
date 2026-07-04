<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Api\Admin\OrganizerUserController as AdminOrganizerUserController;
use App\Http\Controllers\Api\Admin\PasswordController as AdminPasswordController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\TicketTypeController as AdminTicketTypeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MercadoPagoWebhookController;
use App\Http\Controllers\Api\OrderCancellationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Organizer\CategoryController as OrganizerCategoryController;
use App\Http\Controllers\Api\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Api\Organizer\TicketTypeController as OrganizerTicketTypeController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// ─── Autenticação do portal público (client) ──────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Password Reset do portal
Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:3,60');
Route::post('/password/reset', [PasswordResetController::class, 'reset']);

// ─── Autenticação do backoffice (admin) ───────────────────────────────────
Route::post('/admin/auth/login', [AdminAuthController::class, 'login']);
Route::post('/admin/password/forgot', [AdminPasswordController::class, 'sendResetLink'])->middleware('throttle:3,60');
Route::post('/admin/password/reset', [AdminPasswordController::class, 'reset']);
Route::post('/admin/password/activate', [AdminPasswordController::class, 'activate']);

// Email Verification - rota pública para verificar (com assinatura)
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

// Proxy de arquivos do storage (S3)
Route::get('/storage/{path}', [StorageController::class, 'serve'])->where('path', '.+');

// Eventos públicos
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/cities', [EventController::class, 'cities']);
Route::get('/events/states', [EventController::class, 'states']);
Route::get('/events/{event}/categories', [EventController::class, 'categories']);
Route::get('/events/{slug}', [EventController::class, 'show']);

// Pedidos - autenticação opcional (funciona com ou sem token)
Route::post('/orders', [OrderController::class, 'store'])->middleware('optional_auth');
Route::post('/orders/{order}/payment', [OrderController::class, 'processPayment'])->middleware('optional_auth');

// Status público do pedido (para polling de PIX)
Route::get('/orders/{reference}/status', [OrderController::class, 'status']);

// Webhook do Mercado Pago (público)
Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])->name('webhooks.mercadopago');

// ─── Portal público (client) — token Sanctum sobre `users` ────────────────
Route::middleware('auth:client')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Email Verification
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,60');
    Route::get('/email/status', [EmailVerificationController::class, 'status']);

    // Pedidos do comprador autenticado
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order:reference}', [OrderController::class, 'show']);
    Route::post('/orders/{order:reference}/cancel', [OrderController::class, 'cancel']);

    // Solicitação de cancelamento/estorno (um ou mais pedidos pagos)
    Route::post('/orders/cancellations', [OrderCancellationController::class, 'storeBatch']);

    // Tickets do comprador autenticado
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{code}', [TicketController::class, 'show']);
    Route::get('/tickets/{code}/qr', [TicketController::class, 'downloadQr']);
});

// ─── Backoffice (admin) — token Sanctum sobre `admin_users` ────────────────
Route::middleware('auth:admin')->group(function () {
    // Auth
    Route::post('/admin/auth/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/auth/me', [AdminAuthController::class, 'me']);

    // Validação de tickets (staff/admin do organizador — checagem fina no controller)
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
            Route::get('/users', [AdminOrganizerUserController::class, 'index']);
            Route::post('/users', [AdminOrganizerUserController::class, 'store']);
            Route::patch('/users/{user}', [AdminOrganizerUserController::class, 'update']);
            Route::delete('/users/{user}', [AdminOrganizerUserController::class, 'destroy']);
        });

        // CRUD de Eventos
        Route::apiResource('events', AdminEventController::class);

        // CRUD de Categorias (nested em eventos)
        Route::apiResource('events.categories', AdminCategoryController::class);

        // CRUD de Tipos de Ingresso (nested em eventos)
        Route::apiResource('events.ticket-types', AdminTicketTypeController::class);

        // Exportar relatório de inscritos
        Route::get('/events/{event}/report', [AdminReportController::class, 'exportParticipants']);

        // Pedidos do evento
        Route::get('/events/{event}/orders', [AdminOrderController::class, 'index']);

        // Solicitações de cancelamento/estorno
        Route::get('/events/{event}/cancellations', [OrderCancellationController::class, 'index']);
        Route::post('/cancellations/{orderCancellation}/approve', [OrderCancellationController::class, 'approve']);
        Route::post('/cancellations/{orderCancellation}/reject', [OrderCancellationController::class, 'reject']);

        // WhatsApp Gateway
        Route::prefix('whatsapp')->group(function () {
            Route::post('connect', [WhatsAppController::class, 'connect']);
            Route::get('status', [WhatsAppController::class, 'status']);
            Route::delete('session', [WhatsAppController::class, 'disconnect']);
        });
    });

    // Organizers (contexto específico)
    Route::middleware('organizer_access')->prefix('organizer')->group(function () {
        // Dashboard do organizador
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/events/{event}/dashboard', [DashboardController::class, 'eventDashboard']);

        // Visualizar eventos (read-only)
        Route::get('/events', [OrganizerEventController::class, 'index']);
        Route::get('/events/{event}', [OrganizerEventController::class, 'show']);

        // Exportar relatório de inscritos
        Route::get('/events/{event}/report', [AdminReportController::class, 'exportParticipants']);

        // Visualizar categorias (read-only)
        Route::get('/events/{event}/categories', [OrganizerCategoryController::class, 'index']);
        Route::get('/events/{event}/categories/{category}', [OrganizerCategoryController::class, 'show']);

        // Visualizar tipos de ingresso (read-only)
        Route::get('/events/{event}/ticket-types', [OrganizerTicketTypeController::class, 'index']);
        Route::get('/events/{event}/ticket-types/{ticket_type}', [OrganizerTicketTypeController::class, 'show']);
    });
});
