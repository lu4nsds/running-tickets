<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera reservas (reserved_until expirado) a cada minuto.
// O parâmetro --minutes=60 mantém suporte a pedidos legados sem reserved_until.
Schedule::command('orders:cancel-expired --minutes=60')->everyMinute()->withoutOverlapping();

// Remover tokens Sanctum expirados do banco diariamente
Schedule::command('sanctum:prune-expired --hours=8')->daily();
