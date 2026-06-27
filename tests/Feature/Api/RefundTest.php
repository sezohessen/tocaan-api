<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Payments\Gateways\CreditCardGateway;

beforeEach(function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => CreditCardGateway::class,
        'api_key' => 'key',
        'secret' => 'secret',
    ]);
});

function memberPayment($member, float $amount = 100): Payment
{
    $order = Order::factory()->confirmed()->for($member)->create(['total' => $amount]);

    return Payment::factory()->successful()->for($order)->create([
        'amount' => $amount,
        'gateway' => 'credit_card',
        'gateway_reference' => 'ref_original',
    ]);
}

it('lets a member fully refund their own payment', function () {
    $member = actingAsMember();
    $payment = memberPayment($member, 100);

    $this->postJson("/api/v1/member/payments/{$payment->uuid}/refund")
        ->assertCreated()
        ->assertJsonPath('data.amount', fn ($v) => (float) $v === 100.0);

    expect($payment->order->refresh()->status)->toBe(OrderStatus::Refunded);
});

it('lets a member partially refund their own payment', function () {
    $member = actingAsMember();
    $payment = memberPayment($member, 100);

    $this->postJson("/api/v1/member/payments/{$payment->uuid}/refund", ['amount' => 30])
        ->assertCreated()
        ->assertJsonPath('data.amount', fn ($v) => (float) $v === 30.0);

    expect($payment->refresh()->refundableAmount())->toBe(70.0);
});

it('forbids a member from refunding another member payment', function () {
    actingAsMember();
    $payment = memberPayment(member(), 100); // belongs to a different member

    $this->postJson("/api/v1/member/payments/{$payment->uuid}/refund")->assertForbidden();
});

it('rejects an over-refund with 422', function () {
    $member = actingAsMember();
    $payment = memberPayment($member, 100);
    $this->postJson("/api/v1/member/payments/{$payment->uuid}/refund", ['amount' => 80])->assertCreated();

    $this->postJson("/api/v1/member/payments/{$payment->uuid}/refund", ['amount' => 50])
        ->assertStatus(422);
});

it('validates a non-positive refund amount', function () {
    $member = actingAsMember();
    $payment = memberPayment($member, 100);

    $this->postJson("/api/v1/member/payments/{$payment->uuid}/refund", ['amount' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors('amount');
});

it('lets an admin refund any payment', function () {
    actingAsAdmin();
    $payment = memberPayment(member(), 100);

    $this->postJson("/api/v1/admin/payments/{$payment->uuid}/refund", ['amount' => 25])
        ->assertCreated()
        ->assertJsonPath('data.amount', fn ($v) => (float) $v === 25.0);
});

it('lets an admin update any order details', function () {
    actingAsAdmin();
    $order = Order::factory()->create(['tax' => 0, 'discount' => 0]);
    OrderItem::factory()->for($order)->create(['unit_price' => 50, 'quantity' => 2]);

    $this->putJson("/api/v1/admin/orders/{$order->uuid}", ['tax' => 10, 'discount' => 5])
        ->assertOk()
        ->assertJsonPath('data.total', fn ($v) => (float) $v === 105.0);
});
