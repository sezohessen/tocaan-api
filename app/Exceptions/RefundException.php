<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Payment;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RefundException extends RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public static function notSuccessful(Payment $payment): self
    {
        return new self(__('Only successful payments can be refunded.'), 409);
    }

    public static function gatewayNotRefundable(string $gateway): self
    {
        return new self(__('The :gateway gateway does not support refunds.', ['gateway' => $gateway]), 422);
    }

    public static function exceedsRefundable(Payment $payment): self
    {
        return new self(
            __('Refund amount exceeds the refundable balance (:amount remaining).', [
                'amount' => number_format($payment->refundableAmount(), 2),
            ]),
            422
        );
    }

    public static function gatewayDeclined(?string $message): self
    {
        return new self($message ?? __('The gateway declined the refund.'), 402);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }
}
