<?php

declare(strict_types=1);

namespace App\Payments\Results;

final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    private function __construct(
        public bool $successful,
        public string $gateway,
        public float $amount,
        public ?string $reference,
        public ?string $message,
        public array $rawResponse,
    ) {}

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function successful(string $gateway, float $amount, string $reference, array $rawResponse = []): self
    {
        return new self(true, $gateway, $amount, $reference, null, $rawResponse);
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function failed(string $gateway, float $amount, ?string $message = null, array $rawResponse = []): self
    {
        return new self(false, $gateway, $amount, null, $message, $rawResponse);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }
}
