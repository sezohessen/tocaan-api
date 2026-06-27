<?php

declare(strict_types=1);

namespace App\Services\Pricing;

class PricingContext
{
    public float $tax = 0;

    public float $discount = 0;

    public function __construct(
        public readonly float $subtotal,
        public readonly string $currency = 'USD',
    ) {}

    public function total(): float
    {
        return round($this->subtotal + $this->tax - $this->discount, 2);
    }

    /**
     * @return array{subtotal: float, tax: float, discount: float, total: float}
     */
    public function toArray(): array
    {
        return [
            'subtotal' => round($this->subtotal, 2),
            'tax' => round($this->tax, 2),
            'discount' => round($this->discount, 2),
            'total' => $this->total(),
        ];
    }
}
