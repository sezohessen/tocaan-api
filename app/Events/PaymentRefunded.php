<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\OrderRefund;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public OrderRefund $refund,
        public bool $fullyRefunded,
    ) {}
}
