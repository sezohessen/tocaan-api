<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Results\ChargeResult;

class CreditCardGateway extends AbstractGateway
{
    public const NAME = 'credit_card';

    public function name(): string
    {
        return self::NAME;
    }

    public function charge(Payment $payment): ChargeResult
    {
        $this->requireConfig(['api_key', 'secret']);

        if ((float) $payment->amount <= 0) {
            return ChargeResult::failed($this->name(), __('The charge amount must be greater than zero.'));
        }

        $reference = 'cc_'.bin2hex(random_bytes(8));

        return ChargeResult::successful($this->name(), $reference, [
            'gateway' => $this->name(),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'authorized' => true,
        ]);
    }
}
