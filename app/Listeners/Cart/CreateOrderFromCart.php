<?php

declare(strict_types=1);

namespace App\Listeners\Cart;

use App\Enums\OrderStatus;
use App\Events\CartPaid;
use App\Models\CartItem;
use App\Models\Order;

class CreateOrderFromCart
{
    public function handle(CartPaid $event): void
    {
        $cart = $event->cart->loadMissing('items.product');

        $order = Order::create([
            'member_id' => $cart->member_id,
            'status' => OrderStatus::Confirmed,
            'currency' => $cart->currency,
        ]);

        /** @var CartItem $item */
        foreach ($cart->items as $item) {
            $product = $item->product;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'unit_price' => $product->price,
            ]);

            $product->decrement('stock', $item->quantity);
        }

        $subtotal = (float) $order->items()->sum('total');

        $order->forceFill([
            'subtotal' => $subtotal,
            'tax' => $event->tax,
            'discount' => $event->discount,
            'total' => round($subtotal + $event->tax - $event->discount, 2),
        ])->save();

        $event->order = $order;
    }
}
