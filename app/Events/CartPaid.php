<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Results\ChargeResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartPaid
{
    use Dispatchable, SerializesModels;

    public ?Order $order = null;

    public ?Payment $payment = null;

    public function __construct(
        public Cart $cart,
        public ChargeResult $charge,
        public float $tax = 0,
        public float $discount = 0,
    ) {}
}
