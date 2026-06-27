<?php

declare(strict_types=1);

use App\Actions\Payments\ProcessPaymentAction;
use App\Data\ProcessPaymentData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Payments\Gateways\FakeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\Gateways\FailingTestGateway;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payments.gateways.credit_card', [
        'driver' => FakeGateway::class,
        'name' => 'credit_card',
    ]);
});

it('processes a payment for a confirmed order and marks it successful', function () {
    Event::fake([PaymentSucceeded::class]);
    $order = Order::factory()->confirmed()->create(['total' => 200]);

    $payment = app(ProcessPaymentAction::class)
        ->execute($order, new ProcessPaymentData(PaymentMethod::CreditCard));

    expect($payment->status)->toBe(PaymentStatus::Successful)
        ->and((float) $payment->amount)->toBe(200.0)
        ->and($payment->gateway_reference)->not->toBeNull()
        ->and($payment->processed_at)->not->toBeNull();

    Event::assertDispatched(PaymentSucceeded::class);
});

it('rejects payment for an order that is not confirmed', function () {
    $order = Order::factory()->pending()->create();

    expect(fn () => app(ProcessPaymentAction::class)
        ->execute($order, new ProcessPaymentData(PaymentMethod::CreditCard)))
        ->toThrow(OrderException::class);

    expect($order->payments()->count())->toBe(0);
});

it('records a failed payment when the gateway declines', function () {
    Event::fake([PaymentFailed::class]);
    config()->set('payments.gateways.credit_card', [
        'driver' => FailingTestGateway::class,
        'name' => 'credit_card',
    ]);
    $order = Order::factory()->confirmed()->create(['total' => 200]);

    $payment = app(ProcessPaymentAction::class)
        ->execute($order, new ProcessPaymentData(PaymentMethod::CreditCard));

    expect($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->gateway_reference)->toBeNull();

    Event::assertDispatched(PaymentFailed::class);
});

it('does not throw for a cancelled order but refuses via guard', function () {
    $order = Order::factory()->cancelled()->create();

    expect(fn () => app(ProcessPaymentAction::class)
        ->execute($order, new ProcessPaymentData(PaymentMethod::CreditCard)))
        ->toThrow(OrderException::class);
});
