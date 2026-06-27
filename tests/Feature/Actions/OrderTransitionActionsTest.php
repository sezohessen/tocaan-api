<?php

declare(strict_types=1);

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\ConfirmOrderAction;
use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Events\OrderConfirmed;
use App\Exceptions\OrderException;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('confirms a pending order and fires OrderConfirmed', function () {
    Event::fake([OrderConfirmed::class]);
    $order = Order::factory()->pending()->create();

    $result = app(ConfirmOrderAction::class)->execute($order);

    expect($result->status)->toBe(OrderStatus::Confirmed);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Confirmed->value]);
    Event::assertDispatched(OrderConfirmed::class);
});

it('refuses to confirm an order that cannot transition to confirmed', function () {
    Event::fake([OrderConfirmed::class]);
    $order = Order::factory()->cancelled()->create();

    expect(fn () => app(ConfirmOrderAction::class)->execute($order))
        ->toThrow(OrderException::class);

    Event::assertNotDispatched(OrderConfirmed::class);
});

it('cancels a pending or confirmed order and fires OrderCancelled', function () {
    Event::fake([OrderCancelled::class]);
    $order = Order::factory()->confirmed()->create();

    $result = app(CancelOrderAction::class)->execute($order);

    expect($result->status)->toBe(OrderStatus::Cancelled);
    Event::assertDispatched(OrderCancelled::class);
});

it('refuses to cancel a terminal (refunded) order', function () {
    $order = Order::factory()->refunded()->create();

    expect(fn () => app(CancelOrderAction::class)->execute($order))
        ->toThrow(OrderException::class);
});
