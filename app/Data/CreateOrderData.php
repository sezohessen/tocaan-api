<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CreateOrderData extends Data
{
    public function __construct(
        public int $memberId,
        public string $currency,
        /** @var DataCollection<int, OrderItemData> */
        public DataCollection $items,
        public float $tax = 0,
        public float $discount = 0,
        public ?array $meta = null,
    ) {}
}
