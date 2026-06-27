<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Events\OrderCreated;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DispatchOrderWebhook implements ShouldQueue
{
    public string $queue = 'webhooks';

    public function handleCreated(OrderCreated $event): void
    {
        $this->send('order.created', $event->order);
    }

    public function handleConfirmed(OrderConfirmed $event): void
    {
        $this->send('order.confirmed', $event->order);
    }

    private function send(string $type, Order $order): void
    {
        Log::info('Outbound webhook queued', [
            'event' => $type,
            'payload' => OrderResource::make($order->loadMissing('items'))->resolve(),
        ]);
    }
}
