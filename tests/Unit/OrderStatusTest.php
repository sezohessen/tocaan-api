<?php

declare(strict_types=1);

use App\Enums\OrderStatus;

it('allows pending to move to confirmed or cancelled', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed))->toBeTrue()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Cancelled))->toBeTrue()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Refunded))->toBeFalse();
});

it('allows confirmed to move to cancelled or refunded', function () {
    expect(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Cancelled))->toBeTrue()
        ->and(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Refunded))->toBeTrue()
        ->and(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Pending))->toBeFalse();
});

it('treats cancelled and refunded as terminal states', function () {
    expect(OrderStatus::Cancelled->allowedTransitions())->toBe([])
        ->and(OrderStatus::Refunded->allowedTransitions())->toBe([]);
});

it('exposes its backing values', function () {
    expect(OrderStatus::values())->toBe(['pending', 'confirmed', 'cancelled', 'refunded']);
});
