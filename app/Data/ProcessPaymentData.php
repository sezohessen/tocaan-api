<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PaymentMethod;
use Spatie\LaravelData\Data;

class ProcessPaymentData extends Data
{
    public function __construct(
        public PaymentMethod $method,
        public ?array $details = null,
    ) {}
}
