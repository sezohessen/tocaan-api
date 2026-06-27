<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Payments\Results\ChargeResult;

it('builds a successful result', function () {
    $result = ChargeResult::successful('credit_card', 'ref_123', ['authorized' => true]);

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->isFailed())->toBeFalse()
        ->and($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->reference)->toBe('ref_123')
        ->and($result->gateway)->toBe('credit_card')
        ->and($result->rawResponse)->toBe(['authorized' => true]);
});

it('builds a failed result with a message and no reference', function () {
    $result = ChargeResult::failed('paypal', 'declined');

    expect($result->isFailed())->toBeTrue()
        ->and($result->isSuccessful())->toBeFalse()
        ->and($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->message)->toBe('declined')
        ->and($result->reference)->toBeNull();
});

it('builds a pending result', function () {
    $result = ChargeResult::pending('credit_card', 'ref_pending');

    expect($result->isPending())->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Pending)
        ->and($result->reference)->toBe('ref_pending');
});
