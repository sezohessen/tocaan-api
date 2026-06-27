<?php

declare(strict_types=1);

use App\Actions\Cart\RemoveFromCartAction;
use App\Actions\Orders\UpdateOrderAction;
use App\Data\OrderItemData;
use App\Data\UpdateOrderData;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

uses(RefreshDatabase::class);

it('removes a single product line from the cart and leaves the rest', function () {
    $cart = Cart::factory()->create();
    $keep = Product::factory()->create();
    $remove = Product::factory()->create();
    CartItem::factory()->for($cart)->create(['product_id' => $keep->id]);
    CartItem::factory()->for($cart)->create(['product_id' => $remove->id]);

    $result = app(RemoveFromCartAction::class)->execute($cart, $remove);

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->product_id)->toBe($keep->id);
});

it('updates order attributes and recalculates totals', function () {
    $order = Order::factory()->create(['tax' => 0, 'discount' => 0]);
    OrderItem::factory()->for($order)->create(['unit_price' => 30, 'quantity' => 2]); // total 60

    $data = UpdateOrderData::from(['currency' => 'EUR', 'tax' => 10, 'discount' => 5]);

    $result = app(UpdateOrderAction::class)->execute($order, $data);

    expect($result->currency)->toBe('EUR')
        ->and((float) $result->subtotal)->toBe(60.0)
        ->and((float) $result->total)->toBe(65.0);
});

it('replaces order items with fresh product snapshots when items are provided', function () {
    $order = Order::factory()->create(['tax' => 0, 'discount' => 0]);
    OrderItem::factory()->for($order)->create();
    $product = Product::factory()->create(['name' => 'Replacement', 'price' => 25, 'stock' => 10]);

    $data = new UpdateOrderData(
        currency: new Optional,
        tax: new Optional,
        discount: new Optional,
        meta: new Optional,
        items: OrderItemData::collect([
            ['productId' => $product->id, 'quantity' => 3],
        ], DataCollection::class),
    );

    $result = app(UpdateOrderAction::class)->execute($order, $data);

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->product_name)->toBe('Replacement')
        ->and((float) $result->items->first()->unit_price)->toBe(25.0)
        ->and((float) $result->subtotal)->toBe(75.0);
});
