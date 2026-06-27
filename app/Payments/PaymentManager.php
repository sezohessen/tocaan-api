<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('payments.default');
    }

    public function driver($driver = null): PaymentGateway
    {
        /** @var PaymentGateway $gateway */
        $gateway = parent::driver($driver);

        return $gateway;
    }

    protected function createDriver($driver): PaymentGateway
    {
        $config = $this->config->get("payments.gateways.{$driver}");

        if (! $config || empty($config['driver'])) {
            throw new InvalidArgumentException("Payment gateway [{$driver}] is not configured.");
        }

        $class = $config['driver'];

        if (! is_subclass_of($class, PaymentGateway::class)) {
            throw new InvalidArgumentException("Gateway [{$class}] must implement the PaymentGateway contract.");
        }

        return new $class($config);
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        return array_keys((array) $this->config->get('payments.gateways', []));
    }
}
