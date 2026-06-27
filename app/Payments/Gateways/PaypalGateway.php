<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Results\ChargeResult;

class PaypalGateway extends AbstractGateway
{
    public const NAME = 'paypal';

    public function name(): string
    {
        return self::NAME;
    }

    public function charge(Payment $payment): ChargeResult
    {
        $this->requireConfig(['client_id', 'secret']);

        if ((float) $payment->amount <= 0) {
            return ChargeResult::failed($this->name(), __('The charge amount must be greater than zero.'));
        }

        $reference = 'pp_'.bin2hex(random_bytes(8));

        return ChargeResult::successful($this->name(), $reference, [
            'gateway' => $this->name(),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'capture_status' => 'COMPLETED',
        ]);
    }
}
