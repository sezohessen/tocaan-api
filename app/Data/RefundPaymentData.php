<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class RefundPaymentData extends Data
{
    public function __construct(
        public float|Optional|null $amount = null,
        public string|Optional|null $reason = null,
    ) {}
}
