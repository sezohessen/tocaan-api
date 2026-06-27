<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Services\Pricing\Stages\ApplyDiscount;
use App\Services\Pricing\Stages\ApplyTax;
use Illuminate\Pipeline\Pipeline;

class PriceCalculator
{
    public function __construct(private readonly Pipeline $pipeline) {}

    /**
     * Run the subtotal through the ordered pricing stages (tax → discount).
     * New stages (coupons, shipping, fees) slot in here without touching callers.
     */
    public function calculate(float $subtotal, float $tax, float $discount, string $currency = 'USD'): PricingContext
    {
        return $this->pipeline
            ->send(new PricingContext($subtotal, $currency))
            ->through([
                new ApplyTax($tax),
                new ApplyDiscount($discount),
            ])
            ->via('handle')
            ->thenReturn();
    }
}
