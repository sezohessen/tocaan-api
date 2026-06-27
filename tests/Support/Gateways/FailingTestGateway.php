<?php

declare(strict_types=1);

namespace Tests\Support\Gateways;

use App\Models\Payment;
use App\Payments\Gateways\AbstractGateway;
use App\Payments\Results\ChargeResult;

class FailingTestGateway extends AbstractGateway
{
    public function name(): string
    {
        return (string) $this->config('name', 'failing');
    }

    public function charge(Payment $payment): ChargeResult
    {
        return ChargeResult::failed($this->name(), 'declined');
    }
}
