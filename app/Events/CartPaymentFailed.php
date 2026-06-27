<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Cart;
use App\Payments\Results\ChargeResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Cart $cart,
        public ChargeResult $charge,
    ) {}
}
