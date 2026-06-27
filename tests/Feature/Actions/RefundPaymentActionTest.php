<?php

declare(strict_types=1);

use App\Actions\Payments\RefundPaymentAction;
use App\Data\RefundPaymentData;
use App\Enums\OrderStatus;
use App\Events\PaymentRefunded;
use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\FakeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    // credit_card => refundable real gateway
    config()->set('payments.gateways.credit_card', [
        'driver' => CreditCardGateway::class,
        'api_key' => 'key',
        'secret' => 'secret',
    ]);
    // wallet => a gateway that does NOT implement Refundable
    config()->set('payments.gateways.wallet', [
        'driver' => FakeGateway::class,
        'name' => 'wallet',
    ]);
});

function paidOrder(float $amount = 100, string $gateway = 'credit_card'): Payment
{
    $order = Order::factory()->confirmed()->create(['total' => $amount]);

    return Payment::factory()->successful()->for($order)->create([
        'amount' => $amount,
        'gateway' => $gateway,
        'gateway_reference' => 'ref_original',
    ]);
}

it('fully refunds a payment and marks the order refunded', function () {
    Event::fake([PaymentRefunded::class]);
    $payment = paidOrder(100);

    $refund = app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData);

    expect((float) $refund->amount)->toBe(100.0)
        ->and($payment->refresh()->isFullyRefunded())->toBeTrue()
        ->and($payment->order->refresh()->status)->toBe(OrderStatus::Refunded);

    Event::assertDispatched(PaymentRefunded::class, fn ($e) => $e->fullyRefunded === true);
});

it('supports partial refunds and keeps the order confirmed', function () {
    $payment = paidOrder(100);

    app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData(amount: 40));

    expect($payment->refundedAmount())->toBe(40.0)
        ->and($payment->refundableAmount())->toBe(60.0)
        ->and($payment->order->refresh()->status)->toBe(OrderStatus::Confirmed);
});

it('accumulates multiple partial refunds up to the full amount', function () {
    $payment = paidOrder(100);

    app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData(amount: 60));
    app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData(amount: 40));

    expect($payment->refresh()->isFullyRefunded())->toBeTrue()
        ->and($payment->order->refresh()->status)->toBe(OrderStatus::Refunded);
});

it('rejects a refund that exceeds the refundable balance', function () {
    $payment = paidOrder(100);
    app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData(amount: 70));

    expect(fn () => app(RefundPaymentAction::class)->execute($payment->refresh(), new RefundPaymentData(amount: 40)))
        ->toThrow(RefundException::class);

    expect($payment->refresh()->refundedAmount())->toBe(70.0);
});

it('rejects refunding a non-successful payment', function () {
    $order = Order::factory()->confirmed()->create();
    $payment = Payment::factory()->failed()->for($order)->create(['gateway' => 'credit_card']);

    expect(fn () => app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData))
        ->toThrow(RefundException::class);
});

it('rejects refunding through a gateway that does not support refunds', function () {
    $payment = paidOrder(100, 'wallet');

    expect(fn () => app(RefundPaymentAction::class)->execute($payment, new RefundPaymentData))
        ->toThrow(RefundException::class);
});
