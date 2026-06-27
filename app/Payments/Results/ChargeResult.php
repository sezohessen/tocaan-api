<?php

declare(strict_types=1);

namespace App\Payments\Results;

use App\Enums\PaymentStatus;

final readonly class ChargeResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    private function __construct(
        public PaymentStatus $status,
        public string $gateway,
        public ?string $reference,
        public ?string $message,
        public array $rawResponse,
    ) {}

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function successful(string $gateway, string $reference, array $rawResponse = []): self
    {
        return new self(PaymentStatus::Successful, $gateway, $reference, null, $rawResponse);
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function failed(string $gateway, ?string $message = null, array $rawResponse = []): self
    {
        return new self(PaymentStatus::Failed, $gateway, null, $message, $rawResponse);
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function pending(string $gateway, ?string $reference = null, array $rawResponse = []): self
    {
        return new self(PaymentStatus::Pending, $gateway, $reference, null, $rawResponse);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Successful;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }
}
