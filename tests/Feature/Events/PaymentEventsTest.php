<?php

declare(strict_types=1);

use App\Events\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Listeners\DispatchOrderWebhook;
use App\Listeners\SendPaymentNotification;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('registers the order-created listener exactly once', function () {
    expect(Event::hasListeners(OrderCreated::class))->toBeTrue();

    Log::spy();

    event(new OrderCreated(Order::factory()->create()));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message) => $message === 'Outbound webhook queued')
        ->once();
});

it('routes payment succeeded to the notification listener', function () {
    Log::spy();

    $payment = Payment::factory()->successful()->create();
    event(new PaymentSucceeded($payment));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message) => $message === 'Payment succeeded')
        ->once();
});

it('wires the subscriber listeners to the correct handlers', function () {
    $listeners = Event::getRawListeners();

    expect($listeners[OrderCreated::class] ?? [])
        ->toContain([DispatchOrderWebhook::class, 'handleCreated'])
        ->and($listeners[PaymentSucceeded::class] ?? [])
        ->toContain([SendPaymentNotification::class, 'handleSucceeded']);
});
