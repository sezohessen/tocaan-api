<?php

declare(strict_types=1);

use App\Services\Pricing\PriceCalculator;

it('applies tax then discount to the subtotal', function () {
    $result = app(PriceCalculator::class)->calculate(subtotal: 100, tax: 10, discount: 5);

    expect($result->subtotal)->toBe(100.0)
        ->and($result->tax)->toBe(10.0)
        ->and($result->discount)->toBe(5.0)
        ->and($result->total())->toBe(105.0);
});

it('never lets a discount push the total below zero', function () {
    $result = app(PriceCalculator::class)->calculate(subtotal: 50, tax: 0, discount: 999);

    expect($result->discount)->toBe(50.0)
        ->and($result->total())->toBe(0.0);
});

it('ignores negative tax and discount inputs', function () {
    $result = app(PriceCalculator::class)->calculate(subtotal: 100, tax: -10, discount: -5);

    expect($result->tax)->toBe(0.0)
        ->and($result->discount)->toBe(0.0)
        ->and($result->total())->toBe(100.0);
});

it('rounds money to two decimals', function () {
    $result = app(PriceCalculator::class)->calculate(subtotal: 33.333, tax: 1.111, discount: 0);

    expect($result->total())->toBe(34.44);
});
