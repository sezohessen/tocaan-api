<?php

declare(strict_types=1);

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Payments\Gateways\FakeGateway;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\Gateways\FailingTestGateway;

const CART = '/api/v1/member/cart';

beforeEach(function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => FakeGateway::class,
        'name' => 'credit_card',
    ]);
});

it('returns an open cart for the user', function () {
    actingAsMember();

    $this->getJson(CART)
        ->assertOk()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonCount(0, 'data.items');
});

it('adds a product to the cart', function () {
    actingAsMember();
    $product = Product::factory()->create(['price' => 30, 'stock' => 10]);

    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.subtotal', fn ($value) => (float) $value === 60.0);
});

it('merges quantity when adding the same product twice', function () {
    actingAsMember();
    $product = Product::factory()->create(['stock' => 10]);

    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 3])
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.quantity', 4);
});

it('rejects adding more than available stock', function () {
    actingAsMember();
    $product = Product::factory()->create(['stock' => 2]);

    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 5])
        ->assertStatus(422);
});

it('removes a product from the cart', function () {
    actingAsMember();
    $product = Product::factory()->create(['stock' => 10]);
    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $this->deleteJson(CART.'/items/'.$product->id)
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

it('pays the cart, creating a confirmed order with snapshotted items and a payment', function () {
    actingAsMember();
    $product = Product::factory()->create(['name' => 'Snapshot Me', 'price' => 40, 'stock' => 10]);
    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

    $response = $this->postJson(CART.'/checkout', ['method' => 'credit_card', 'tax' => 5, 'discount' => 10]);

    $response->assertCreated()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value)
        ->assertJsonPath('data.subtotal', fn ($v) => (float) $v === 80.0)
        ->assertJsonPath('data.total', fn ($v) => (float) $v === 75.0)
        ->assertJsonPath('data.items.0.product_name', 'Snapshot Me');
});

it('runs the CartPaid side effects: order, payment, stock, cart cleanup', function () {
    $member = actingAsMember();
    $product = Product::factory()->create(['price' => 40, 'stock' => 10]);
    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

    $this->postJson(CART.'/checkout', ['method' => 'credit_card'])->assertCreated();

    expect(Order::where('member_id', $member->id)->where('status', OrderStatus::Confirmed)->count())->toBe(1)
        ->and(Payment::where('status', PaymentStatus::Successful)->count())->toBe(1)
        ->and($product->refresh()->stock)->toBe(8);

    $this->assertSoftDeleted('carts', ['member_id' => $member->id]);
});

it('requires a payment method to checkout', function () {
    actingAsMember();
    $product = Product::factory()->create(['stock' => 5]);
    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $this->postJson(CART.'/checkout')->assertStatus(422)->assertJsonValidationErrors('method');
});

it('rejects checkout of an empty cart', function () {
    actingAsMember();
    $this->getJson(CART)->assertOk();

    $this->postJson(CART.'/checkout', ['method' => 'credit_card'])->assertStatus(422);
});

it('returns a payment error when the gateway declines', function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => FailingTestGateway::class,
        'name' => 'credit_card',
    ]);
    actingAsMember();
    $product = Product::factory()->create(['price' => 40, 'stock' => 10]);
    $this->postJson(CART.'/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $this->postJson(CART.'/checkout', ['method' => 'credit_card'])->assertStatus(402);

    expect(Order::count())->toBe(0);
});

it('prunes abandoned carts but keeps checked-out ones', function () {
    config()->set('payments.abandoned_cart_ttl_days', 30);

    $stale = Cart::factory()->create(['status' => CartStatus::Open]);
    $stale->forceFill(['updated_at' => now()->subDays(31)])->save();

    $checkedOut = Cart::factory()->create(['status' => CartStatus::CheckedOut]);
    $checkedOut->forceFill(['updated_at' => now()->subDays(31)])->save();

    $recent = Cart::factory()->create(['status' => CartStatus::Open]);

    Artisan::call('model:prune', ['--model' => [Cart::class]]);

    expect(Cart::withTrashed()->find($stale->id))->toBeNull()
        ->and(Cart::find($checkedOut->id))->not->toBeNull()
        ->and(Cart::find($recent->id))->not->toBeNull();
});
