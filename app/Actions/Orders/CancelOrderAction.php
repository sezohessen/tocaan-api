<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Exceptions\OrderException;
use App\Models\Order;

class CancelOrderAction
{
    public function execute(Order $order): Order
    {
        if (! $order->status->canTransitionTo(OrderStatus::Cancelled)) {
            throw new OrderException(
                __('Order :uuid cannot be cancelled from its current status.', ['uuid' => $order->uuid])
            );
        }

        $order->update(['status' => OrderStatus::Cancelled]);

        event(new OrderCancelled($order));

        return $order;
    }
}
