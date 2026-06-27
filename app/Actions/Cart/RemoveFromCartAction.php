<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Product;

class RemoveFromCartAction
{
    public function execute(Cart $cart, Product $product): Cart
    {
        $cart->items()->where('product_id', $product->id)->delete();

        return $cart->load('items.product');
    }
}
