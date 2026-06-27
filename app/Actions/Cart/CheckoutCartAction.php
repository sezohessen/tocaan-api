<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Data\ProcessPaymentData;
use App\Events\CartPaid;
use App\Events\CartPaymentFailed;
use App\Exceptions\CartException;
use App\Models\Cart;
use App\Models\Order;
use App\Payments\PaymentManager;
use App\Payments\Results\ChargeResult;
use Illuminate\Support\Facades\DB;

class CheckoutCartAction
{
    public function __construct(private readonly PaymentManager $gateways) {}

    /**
     * @param  array{tax?: float, discount?: float}  $options
     */
    public function execute(Cart $cart, ProcessPaymentData $payment, array $options = []): Order
    {
        $tax = (float) ($options['tax'] ?? 0);
        $discount = (float) ($options['discount'] ?? 0);

        return DB::transaction(function () use ($cart, $payment, $tax, $discount): Order {
            $locked = Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();
            $locked->load('items.product');

            $this->guardPayable($locked);

            $charge = $this->charge($locked, $payment, $tax, $discount);

            if (! $charge->isSuccessful()) {
                event(new CartPaymentFailed($locked, $charge));

                throw CartException::paymentFailed($charge->message);
            }

            $event = new CartPaid($locked, $charge, $tax, $discount);
            event($event);

            return $event->order->load('items', 'payments');
        });
    }

    private function guardPayable(Cart $cart): void
    {
        if (! $cart->isOpen()) {
            throw CartException::notOpen($cart);
        }

        if ($cart->isEmpty()) {
            throw CartException::empty($cart);
        }

        foreach ($cart->items as $item) {
            if (! $item->product->isAvailable($item->quantity)) {
                throw CartException::productUnavailable($item->product);
            }
        }
    }

    private function charge(Cart $cart, ProcessPaymentData $payment, float $tax, float $discount): ChargeResult
    {
        $total = round($cart->subtotal() + $tax - $discount, 2);

        $pending = $cart->buildPayment($total);

        return $this->gateways->driver($payment->method->value)->charge($pending);
    }
}
