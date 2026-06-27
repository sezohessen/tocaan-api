<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Exceptions\CartException;
use App\Models\Product;
use Illuminate\Support\Collection;

class InventoryManager
{
    /**
     * Lock the given products FOR UPDATE, verify availability under the lock,
     * then decrement stock atomically. Must run inside a transaction.
     *
     * @param  array<int, int>  $quantities  product_id => quantity
     * @return Collection<int, Product> locked products keyed by id
     */
    public function reserve(array $quantities): Collection
    {
        $ids = array_keys($quantities);

        $products = Product::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product || ! $product->isAvailable($quantity)) {
                throw CartException::productUnavailable(
                    $product ?? new Product(['name' => "#{$productId}"])
                );
            }
        }

        foreach ($quantities as $productId => $quantity) {
            $products->get($productId)->decrement('stock', $quantity);
        }

        return $products;
    }
}
