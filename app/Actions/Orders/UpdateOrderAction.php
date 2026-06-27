<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Data\OrderItemData;
use App\Data\UpdateOrderData;
use App\Exceptions\CartException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdateOrderAction
{
    public function execute(Order $order, UpdateOrderData $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $this->updateAttributes($order, $data);

            if (! $data->items instanceof Optional) {
                $this->replaceItems($order, $data);
            }

            $this->recalculateTotals($order);

            return $order->refresh()->load('items');
        });
    }

    private function updateAttributes(Order $order, UpdateOrderData $data): void
    {
        if (! $data->currency instanceof Optional) {
            $order->currency = $data->currency;
        }

        if (! $data->tax instanceof Optional) {
            $order->tax = $data->tax;
        }

        if (! $data->discount instanceof Optional) {
            $order->discount = $data->discount;
        }

        if (! $data->meta instanceof Optional) {
            $order->meta = $data->meta;
        }

        $order->save();
    }

    private function replaceItems(Order $order, UpdateOrderData $data): void
    {
        $order->items()->delete();

        /** @var OrderItemData $item */
        foreach ($data->items as $item) {
            $product = Product::find($item->productId);

            if (! $product || ! $product->isAvailable($item->quantity)) {
                throw CartException::productUnavailable($product ?? new Product(['name' => "#{$item->productId}"]));
            }

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'unit_price' => $product->price,
            ]);
        }
    }

    private function recalculateTotals(Order $order): void
    {
        $subtotal = (float) $order->items()->sum('total');

        $order->forceFill([
            'subtotal' => $subtotal,
            'total' => round($subtotal + (float) $order->tax - (float) $order->discount, 2),
        ])->save();
    }
}
