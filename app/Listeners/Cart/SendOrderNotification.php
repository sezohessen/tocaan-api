<?php

declare(strict_types=1);

namespace App\Listeners\Cart;

use App\Events\CartPaid;
use Illuminate\Support\Facades\Log;

class SendOrderNotification
{
    public function handle(CartPaid $event): void
    {
        if (! $event->order) {
            return;
        }

        Log::info('Order confirmation notification', [
            'order' => $event->order->uuid,
            'member' => $event->order->member_id,
            'total' => $event->order->total,
        ]);
    }
}
