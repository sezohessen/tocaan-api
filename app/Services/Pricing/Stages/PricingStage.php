<?php

declare(strict_types=1);

namespace App\Services\Pricing\Stages;

use App\Services\Pricing\PricingContext;
use Closure;

interface PricingStage
{
    public function handle(PricingContext $context, Closure $next): PricingContext;
}
