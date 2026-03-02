<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Jobs\GenerateOrderTicketsJob;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Apenas log - o email será enviado no updated() após metadata ser salvo
        Log::info('OrderObserver::created disparado', [
            'order_id' => $order->id,
            'status' => $order->status->value,
        ]);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Verifica se o status mudou para PAID
        if ($order->isDirty('status') && $order->status === OrderStatus::PAID) {
            // Invalidar cache dos dashboards
            $order->load('event.organizer');
            Cache::forget("dashboard_organizer_{$order->event->organizer_id}");
            Cache::forget("dashboard_event_{$order->event_id}");
            Cache::forget("dashboard_admin_platform");
            Cache::forget("dashboard_admin_organizer_{$order->event->organizer_id}");
            
            // Dispatch do job para gerar os tickets
            GenerateOrderTicketsJob::dispatch($order);

            // Envia email de confirmação de pagamento após tickets serem gerados
            // O email será enviado pelo job após a geração dos tickets
        }
    }
}
