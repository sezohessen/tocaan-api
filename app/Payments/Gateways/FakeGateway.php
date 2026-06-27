<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Results\ChargeResult;

class FakeGateway extends AbstractGateway
{
    public const NAME = 'fake';

    private bool $shouldSucceed = true;

    private ?string $forcedMessage = null;

    public function name(): string
    {
        return (string) $this->config('name', self::NAME);
    }

    public function shouldSucceed(bool $succeed = true): self
    {
        $this->shouldSucceed = $succeed;

        return $this;
    }

    public function shouldFail(string $message = 'Forced failure.'): self
    {
        $this->shouldSucceed = false;
        $this->forcedMessage = $message;

        return $this;
    }

    public function charge(Payment $payment): ChargeResult
    {
        if (! $this->shouldSucceed) {
            return ChargeResult::failed($this->name(), $this->forcedMessage ?? 'Forced failure.');
        }

        return ChargeResult::successful($this->name(), 'fake_'.bin2hex(random_bytes(6)), [
            'gateway' => $this->name(),
            'amount' => (float) $payment->amount,
        ]);
    }
}
