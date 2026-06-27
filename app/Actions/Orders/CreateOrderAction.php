<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use App\Services\Inventory\InventoryManager;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __construct(
        private readonly InventoryManager $inventory,
        private readonly PriceCalculator $pricing,
    ) {}

    public function execute(CreateOrderData $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $products = $this->inventory->reserve($this->quantities($data));

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
     * @return array<int, int>
     */
    private function quantities(CreateOrderData $data): array
    {
        $quantities = [];

        /** @var OrderItemData $item */
        foreach ($data->items as $item) {
            $quantities[$item->productId] = ($quantities[$item->productId] ?? 0) + $item->quantity;
        }

        return $quantities;
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
        }
    }

    private function applyTotals(Order $order, float $tax, float $discount): void
    {
        $subtotal = (float) $order->items()->sum('total');

        $pricing = $this->pricing->calculate($subtotal, $tax, $discount, $order->currency);

        $order->forceFill($pricing->toArray())->save();
    }
}
