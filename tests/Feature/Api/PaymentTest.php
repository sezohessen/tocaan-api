<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Gateways\FakeGateway;
use Tests\Support\Gateways\FailingTestGateway;

const MEMBER_ORDERS = '/api/v1/member/orders';

beforeEach(function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => FakeGateway::class,
        'name' => 'credit_card',
    ]);
});

it('processes a payment for a confirmed order', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->confirmed()->create(['total' => 150]);

    $this->postJson(MEMBER_ORDERS.'/'.$order->uuid.'/payments', ['method' => 'credit_card'])
        ->assertOk()
        ->assertJsonPath('data.status', PaymentStatus::Successful->value)
        ->assertJsonPath('data.amount', fn ($v) => (float) $v === 150.0);

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'status' => PaymentStatus::Successful->value,
    ]);
});

it('rejects payment for a non-confirmed order', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->pending()->create();

    $this->postJson(MEMBER_ORDERS.'/'.$order->uuid.'/payments', ['method' => 'credit_card'])
        ->assertStatus(422);

    expect($order->payments()->count())->toBe(0);
});

it('records a failed payment when the gateway declines', function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => FailingTestGateway::class,
        'name' => 'credit_card',
    ]);
    $member = actingAsMember();
    $order = Order::factory()->for($member)->confirmed()->create(['total' => 150]);

    $this->postJson(MEMBER_ORDERS.'/'.$order->uuid.'/payments', ['method' => 'credit_card'])
        ->assertOk()
        ->assertJsonPath('data.status', PaymentStatus::Failed->value);
});

it('validates the payment method', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->confirmed()->create();

    $this->postJson(MEMBER_ORDERS.'/'.$order->uuid.'/payments', ['method' => 'bitcoin'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('method');
});

it('lists payments for an order', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->confirmed()->create();
    Payment::factory()->for($order)->count(2)->create();

    $this->getJson(MEMBER_ORDERS.'/'.$order->uuid.'/payments')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists all payments for the current member', function () {
    $member = actingAsMember();
    $order = Order::factory()->for($member)->create();
    Payment::factory()->for($order)->create();
    Payment::factory()->create();

    $this->getJson('/api/v1/member/payments')->assertOk()->assertJsonCount(1, 'data');
});
