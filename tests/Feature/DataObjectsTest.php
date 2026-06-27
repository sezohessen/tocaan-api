<?php

declare(strict_types=1);

use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use Spatie\LaravelData\DataCollection;

it('builds an order item data object from product id and quantity', function () {
    $item = new OrderItemData(productId: 7, quantity: 3);

    expect($item->productId)->toBe(7)
        ->and($item->quantity)->toBe(3);
});

it('collects create-order items into a data collection', function () {
    $data = new CreateOrderData(
        memberId: 1,
        currency: 'USD',
        items: OrderItemData::collect([
            ['productId' => 1, 'quantity' => 2],
            ['productId' => 2, 'quantity' => 1],
        ], DataCollection::class),
        tax: 2.0,
        discount: 1.0,
    );

    expect($data->items)->toHaveCount(2)
        ->and($data->tax)->toBe(2.0)
        ->and($data->discount)->toBe(1.0);
});
