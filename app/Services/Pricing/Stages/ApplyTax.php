<?php

declare(strict_types=1);

namespace App\Services\Pricing\Stages;

use App\Services\Pricing\PricingContext;
use Closure;

class ApplyTax implements PricingStage
{
    public function __construct(private readonly float $amount) {}

    public function handle(PricingContext $context, Closure $next): PricingContext
    {
        $context->tax += max(0, $this->amount);

        return $next($context);
    }
}
