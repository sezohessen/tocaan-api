<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;

const ORDERS = '/api/v1/member/orders';

it('creates an order from product references with server-side pricing', function () {
    actingAsMember();
    $a = Product::factory()->create(['price' => 50, 'stock' => 10]);
    $b = Product::factory()->create(['price' => 25, 'stock' => 10]);

    $this->postJson(ORDERS, [
        'items' => [
            ['product_id' => $a->id, 'quantity' => 2],
            ['product_id' => $b->id, 'quantity' => 1],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.subtotal', fn ($v) => (float) $v === 125.0)
        ->assertJsonPath('data.total', fn ($v) => (float) $v === 125.0)
        ->assertJsonCount(2, 'data.items');
});

it('ignores any client-sent price and uses the catalog price', function () {
    actingAsMember();
    $product = Product::factory()->create(['price' => 100, 'stock' => 10]);

    $this->postJson(ORDERS, [
        'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total', fn ($v) => (float) $v === 100.0);
});

it('validates order creation requires items', function () {
    actingAsMember();

    $this->postJson(ORDERS, [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');
});

it('lists only the current member orders', function () {
    $member = actingAsMember();
    Order::factory()->for($member)->count(2)->create();
    Order::factory()->count(3)->create();

    $this->getJson(ORDERS)->assertOk()->assertJsonCount(2, 'data');
});

it('filters orders by status', function () {
    $member = actingAsMember();
    Order::factory()->for($member)->confirmed()->count(2)->create();
    Order::factory()->for($member)->pending()->create();

    $this->getJson(ORDERS.'?status=confirmed')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('forbids viewing another member order', function () {
    actingAsMember();
    $other = Order::factory()->create();

    $this->getJson(ORDERS.'/'.$other->uuid)->assertForbidden();
});

it('confirms a pending order', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->pending()->create();

    $this->postJson(ORDERS.'/'.$order->uuid.'/confirm')
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);
});

it('deletes an order with no payments', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->create();

    $this->deleteJson(ORDERS.'/'.$order->uuid)->assertNoContent();
    $this->assertSoftDeleted('orders', ['id' => $order->id]);
});

it('blocks deleting an order that has payments', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->create();
    Payment::factory()->for($order)->create();

    $this->deleteJson(ORDERS.'/'.$order->uuid)->assertStatus(409);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'deleted_at' => null]);
});
