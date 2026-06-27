<?php

declare(strict_types=1);

namespace App\Listeners\Cart;

use App\Enums\PaymentStatus;
use App\Events\CartPaid;
use App\Models\Payment;

class RecordPayment
{
    public function handle(CartPaid $event): void
    {
        if (! $event->order) {
            return;
        }

        /** @var Payment $payment */
        $payment = $event->order->payments()->create([
            'status' => PaymentStatus::Successful,
            'gateway' => $event->charge->gateway,
            'method' => $event->charge->gateway,
            'amount' => $event->order->total,
            'currency' => $event->order->currency,
            'gateway_reference' => $event->charge->reference,
            'gateway_response' => $event->charge->rawResponse,
            'processed_at' => now(),
        ]);

        $event->payment = $payment;
    }
}
