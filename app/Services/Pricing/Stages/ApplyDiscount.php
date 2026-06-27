<?php

declare(strict_types=1);

namespace App\Services\Pricing\Stages;

use App\Services\Pricing\PricingContext;
use Closure;

class ApplyDiscount implements PricingStage
{
    public function __construct(private readonly float $amount) {}

    public function handle(PricingContext $context, Closure $next): PricingContext
    {
        // A discount can never exceed the taxed subtotal (no negative totals).
        $cap = $context->subtotal + $context->tax;
        $context->discount += min(max(0, $this->amount), $cap);

        return $next($context);
    }
}
