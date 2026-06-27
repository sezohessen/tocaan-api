<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class UpdateOrderData extends Data
{
    public function __construct(
        public string|Optional $currency,
        public float|Optional $tax,
        public float|Optional $discount,
        public array|Optional|null $meta,
        /** @var DataCollection<int, OrderItemData>|Optional */
        public DataCollection|Optional $items,
    ) {}
}
