<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Product;
use App\Payments\Gateways\FakeGateway;

const IDEM_ORDERS = '/api/v1/member/orders';

it('creates only one order when the same idempotency key is replayed', function () {
    actingAsMember();
    $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
    $payload = ['items' => [['product_id' => $product->id, 'quantity' => 2]]];
    $headers = ['Idempotency-Key' => 'order-key-123'];

    $first = $this->postJson(IDEM_ORDERS, $payload, $headers)->assertCreated();
    $second = $this->postJson(IDEM_ORDERS, $payload, $headers)->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(Order::count())->toBe(1);
});

it('creates separate orders for different idempotency keys', function () {
    actingAsMember();
    $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
    $payload = ['items' => [['product_id' => $product->id, 'quantity' => 1]]];

    $this->postJson(IDEM_ORDERS, $payload, ['Idempotency-Key' => 'key-a'])->assertCreated();
    $this->postJson(IDEM_ORDERS, $payload, ['Idempotency-Key' => 'key-b'])->assertCreated();

    expect(Order::count())->toBe(2);
});

it('rejects reusing an idempotency key with different parameters', function () {
    actingAsMember();
    $a = Product::factory()->create(['price' => 50, 'stock' => 10]);
    $b = Product::factory()->create(['price' => 50, 'stock' => 10]);
    $headers = ['Idempotency-Key' => 'reused-key'];

    $this->postJson(IDEM_ORDERS, ['items' => [['product_id' => $a->id, 'quantity' => 1]]], $headers)
        ->assertCreated();

    $this->postJson(IDEM_ORDERS, ['items' => [['product_id' => $b->id, 'quantity' => 1]]], $headers)
        ->assertStatus(422);

    expect(Order::count())->toBe(1);
});

it('still works without an idempotency key', function () {
    actingAsMember();
    $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
    $payload = ['items' => [['product_id' => $product->id, 'quantity' => 1]]];

    $this->postJson(IDEM_ORDERS, $payload)->assertCreated();
    $this->postJson(IDEM_ORDERS, $payload)->assertCreated();

    expect(Order::count())->toBe(2);
});

it('replays checkout idempotently', function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => FakeGateway::class,
        'name' => 'credit_card',
    ]);
    $member = actingAsMember();
    $product = Product::factory()->create(['price' => 40, 'stock' => 10]);
    $this->postJson('/api/v1/member/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $headers = ['Idempotency-Key' => 'checkout-key'];
    $first = $this->postJson('/api/v1/member/cart/checkout', ['method' => 'credit_card'], $headers)->assertCreated();
    $second = $this->postJson('/api/v1/member/cart/checkout', ['method' => 'credit_card'], $headers)->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(Order::count())->toBe(1);
});
