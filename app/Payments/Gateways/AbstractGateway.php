<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use InvalidArgumentException;

abstract class AbstractGateway implements PaymentGateway
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config = []) {}

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function requireConfig(array $keys): void
    {
        foreach ($keys as $key) {
            if (blank($this->config($key))) {
                throw new InvalidArgumentException(
                    sprintf('Missing required configuration "%s" for the [%s] gateway.', $key, $this->name())
                );
            }
        }
    }
}
