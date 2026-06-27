<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\Results\ChargeResult;

interface PaymentGateway
{
    public function name(): string;

    public function charge(Payment $payment): ChargeResult;
}
