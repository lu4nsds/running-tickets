<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera reservas (reserved_until expirado) a cada 5 minutos.
// O parâmetro --minutes=60 mantém suporte a pedidos legados sem reserved_until.
Schedule::command('orders:cancel-expired --minutes=60')
    ->withoutOverlapping(10)
    ->everyFiveMinutes();

// Reconcilia pedidos de cartão presos em PROCESSING consultando o MP, caso o
// webhook não tenha chegado (ex.: ambiente local ou atraso do gateway).
Schedule::command('payments:reconcile-pending --minutes=30')
    ->withoutOverlapping(10)
    ->everyFiveMinutes();

// Remover tokens Sanctum expirados do banco diariamente
Schedule::command('sanctum:prune-expired --hours=8')
    ->dailyAt('03:00');

// Renova os access tokens OAuth do Mercado Pago (split) perto de vencer.
Schedule::command('mercadopago:refresh-tokens')
    ->withoutOverlapping(10)
    ->hourly();
