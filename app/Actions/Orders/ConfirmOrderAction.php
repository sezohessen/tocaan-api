<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderConfirmed;
use App\Exceptions\OrderException;
use App\Models\Order;

class ConfirmOrderAction
{
    public function execute(Order $order): Order
    {
        if (! $order->status->canTransitionTo(OrderStatus::Confirmed)) {
            throw new OrderException(
                __('Order :uuid cannot be confirmed from its current status.', ['uuid' => $order->uuid])
            );
        }

        $order->update(['status' => OrderStatus::Confirmed]);

        event(new OrderConfirmed($order));

        return $order;
    }
}
