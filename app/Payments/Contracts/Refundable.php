<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\Results\RefundResult;

interface Refundable
{
    public function refund(Payment $payment, float $amount): RefundResult;
}
