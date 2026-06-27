<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Cart;
use App\Models\Product;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CartException extends RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public static function productUnavailable(Product $product): self
    {
        return new self(
            __('Product ":name" is not available in the requested quantity.', ['name' => $product->name])
        );
    }

    public static function notOpen(Cart $cart): self
    {
        return new self(__('This cart has already been checked out.'), 409);
    }

    public static function empty(Cart $cart): self
    {
        return new self(__('Cannot checkout an empty cart.'));
    }

    public static function paymentFailed(?string $message = null): self
    {
        return new self($message ?? __('The payment could not be processed.'), 402);
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
