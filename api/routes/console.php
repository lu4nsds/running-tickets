<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar cancelamento de pedidos expirados a cada 5 minutos
// Por padrão cancela pedidos pendentes há mais de 30 minutos
Schedule::command('orders:cancel-expired --minutes=30')->everyFiveMinutes();

// Remover tokens Sanctum expirados do banco diariamente
Schedule::command('sanctum:prune-expired --hours=8')->daily();
