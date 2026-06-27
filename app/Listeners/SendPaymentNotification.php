<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handleSucceeded(PaymentSucceeded $event): void
    {
        Log::info('Payment succeeded', [
            'payment' => $event->payment->uuid,
            'order' => $event->payment->order_id,
            'amount' => $event->payment->amount,
        ]);
    }

    public function handleFailed(PaymentFailed $event): void
    {
        Log::warning('Payment failed', [
            'payment' => $event->payment->uuid,
            'order' => $event->payment->order_id,
        ]);
    }

    public function handleRefunded(PaymentRefunded $event): void
    {
        Log::info('Payment refunded', [
            'payment' => $event->payment->uuid,
            'refund' => $event->refund->uuid,
            'amount' => $event->refund->amount,
            'fully_refunded' => $event->fullyRefunded,
        ]);
    }
}
