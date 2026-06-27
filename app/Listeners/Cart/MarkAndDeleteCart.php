<?php

declare(strict_types=1);

namespace App\Listeners\Cart;

use App\Enums\CartStatus;
use App\Events\CartPaid;

class MarkAndDeleteCart
{
    public function handle(CartPaid $event): void
    {
        $event->cart->update([
            'status' => CartStatus::CheckedOut,
            'order_id' => $event->order?->id,
        ]);

        $event->cart->delete();
    }
}
