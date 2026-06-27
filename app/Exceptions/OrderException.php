<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Order;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class OrderException extends RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public static function cannotDeleteWithPayments(Order $order): self
    {
        return new self(
            __('Order :uuid cannot be deleted because it has associated payments.', ['uuid' => $order->uuid]),
            409
        );
    }

    public static function notConfirmed(Order $order): self
    {
        return new self(
            __('Payments can only be processed for confirmed orders.'),
            422
        );
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
