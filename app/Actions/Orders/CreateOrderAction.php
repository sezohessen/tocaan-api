<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Exceptions\CartException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function execute(CreateOrderData $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $products = $this->resolveProducts($data);

            $order = Order::create([
                'member_id' => $data->memberId,
                'status' => OrderStatus::Pending,
                'currency' => $data->currency,
                'meta' => $data->meta,
            ]);

            $this->snapshotItems($order, $data, $products);
            $this->applyTotals($order, $data->tax, $data->discount);

            return $order;
        });

        event(new OrderCreated($order));

        return $order->load('items');
    }

    /**
     * @return Collection<int, Product>
     */
    private function resolveProducts(CreateOrderData $data): Collection
    {
        $ids = collect($data->items->items())->map(fn (OrderItemData $item) => $item->productId);

        $products = Product::query()->whereIn('id', $ids)->get()->keyBy('id');

        /** @var OrderItemData $item */
        foreach ($data->items as $item) {
            $product = $products->get($item->productId);

            if (! $product || ! $product->isAvailable($item->quantity)) {
                throw CartException::productUnavailable($product ?? new Product(['name' => "#{$item->productId}"]));
            }
        }

        return $products;
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function snapshotItems(Order $order, CreateOrderData $data, Collection $products): void
    {
        /** @var OrderItemData $item */
        foreach ($data->items as $item) {
            $product = $products->get($item->productId);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'unit_price' => $product->price,
            ]);

            $product->decrement('stock', $item->quantity);
        }
    }

    private function applyTotals(Order $order, float $tax, float $discount): void
    {
        $subtotal = (float) $order->items()->sum('total');

        $order->forceFill([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => round($subtotal + $tax - $discount, 2),
        ])->save();
    }
}
