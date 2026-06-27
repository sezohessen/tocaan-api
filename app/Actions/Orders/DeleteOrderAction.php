<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Exceptions\OrderException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DeleteOrderAction
{
    public function execute(Order $order): void
    {
        if (! $order->canBeDeleted()) {
            throw OrderException::cannotDeleteWithPayments($order);
        }

        DB::transaction(function () use ($order): void {
            $order->items()->delete();
            $order->delete();
        });
    }
}
