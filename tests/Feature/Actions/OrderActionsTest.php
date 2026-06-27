<?php

declare(strict_types=1);

use App\Actions\Orders\CreateOrderAction;
use App\Actions\Orders\DeleteOrderAction;
use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Exceptions\OrderException;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelData\DataCollection;

uses(RefreshDatabase::class);

it('creates an order with items and computed totals', function () {
    Event::fake([OrderCreated::class]);
    $member = Member::factory()->create();
    $keyboard = Product::factory()->create(['price' => 50.0, 'stock' => 10]);
    $mouse = Product::factory()->create(['price' => 25.0, 'stock' => 10]);

    $data = new CreateOrderData(
        memberId: $member->id,
        currency: 'USD',
        items: OrderItemData::collect([
            ['productId' => $keyboard->id, 'quantity' => 2],
            ['productId' => $mouse->id, 'quantity' => 1],
        ], DataCollection::class),
        tax: 5.0,
        discount: 10.0,
    );

    $order = app(CreateOrderAction::class)->execute($data);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and((float) $order->subtotal)->toBe(125.0)
        ->and((float) $order->total)->toBe(120.0)
        ->and($order->items)->toHaveCount(2)
        ->and($order->items->first()->product_id)->toBe($keyboard->id);

    expect($keyboard->refresh()->stock)->toBe(8);

    Event::assertDispatched(OrderCreated::class);
});

it('deletes an order that has no payments', function () {
    $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

    app(DeleteOrderAction::class)->execute($order);

    expect(Order::find($order->id))->toBeNull()
        ->and(Order::withTrashed()->find($order->id))->not->toBeNull();
});

it('refuses to delete an order that has payments', function () {
    $order = Order::factory()->create();
    Payment::factory()->for($order)->create();

    expect(fn () => app(DeleteOrderAction::class)->execute($order))
        ->toThrow(OrderException::class);

    expect(Order::find($order->id))->not->toBeNull();
});
