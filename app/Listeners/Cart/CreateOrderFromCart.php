<?php

declare(strict_types=1);

namespace App\Listeners\Cart;

use App\Enums\OrderStatus;
use App\Events\CartPaid;
use App\Models\CartItem;
use App\Models\Order;
use App\Services\Inventory\InventoryManager;
use App\Services\Pricing\PriceCalculator;

class CreateOrderFromCart
{
    public function __construct(
        private readonly InventoryManager $inventory,
        private readonly PriceCalculator $pricing,
    ) {}

    public function handle(CartPaid $event): void
    {
        $cart = $event->cart->loadMissing('items.product');

        $quantities = [];
        /** @var CartItem $item */
        foreach ($cart->items as $item) {
            $quantities[$item->product_id] = ($quantities[$item->product_id] ?? 0) + $item->quantity;
        }

        $products = $this->inventory->reserve($quantities);

        $order = Order::create([
            'member_id' => $cart->member_id,
            'status' => OrderStatus::Confirmed,
            'currency' => $cart->currency,
        ]);

        /** @var CartItem $item */
        foreach ($cart->items as $item) {
            $product = $products->get($item->product_id);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'unit_price' => $product->price,
            ]);
        }

        $subtotal = (float) $order->items()->sum('total');

        $pricing = $this->pricing->calculate($subtotal, $event->tax, $event->discount, $order->currency);

        $order->forceFill($pricing->toArray())->save();

        $event->order = $order;
    }
}
