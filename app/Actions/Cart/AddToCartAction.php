<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Enums\CartStatus;
use App\Exceptions\CartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AddToCartAction
{
    public function execute(Member $member, Product $product, int $quantity): Cart
    {
        if (! $product->isAvailable($quantity)) {
            throw CartException::productUnavailable($product);
        }

        return DB::transaction(function () use ($member, $product, $quantity): Cart {
            $cart = $this->openCartFor($member);

            /** @var CartItem $item */
            $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
            $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
            $item->save();

            return $cart->load('items.product');
        });
    }

    private function openCartFor(Member $member): Cart
    {
        /** @var Cart $cart */
        $cart = $member->carts()->firstOrCreate(
            ['status' => CartStatus::Open],
            ['currency' => config('app.currency', 'USD')]
        );

        return $cart;
    }
}
