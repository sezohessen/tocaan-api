<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CartPaid;
use App\Events\MemberRegistered;
use App\Events\OrderConfirmed;
use App\Events\OrderCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use App\Events\UserRegistered;
use App\Listeners\Cart\CreateOrderFromCart;
use App\Listeners\Cart\MarkAndDeleteCart;
use App\Listeners\Cart\RecordPayment;
use App\Listeners\Cart\SendOrderNotification;
use Illuminate\Events\Dispatcher;

class PaymentEventSubscriber
{
    /**
     * @return array<string, array<int, array<int, string>|string>>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            UserRegistered::class => [
                SendWelcomeNotification::class,
            ],
            MemberRegistered::class => [
                SendWelcomeNotification::class,
            ],
            CartPaid::class => [
                CreateOrderFromCart::class,
                RecordPayment::class,
                SendOrderNotification::class,
                MarkAndDeleteCart::class,
            ],
            OrderCreated::class => [
                [DispatchOrderWebhook::class, 'handleCreated'],
            ],
            OrderConfirmed::class => [
                [DispatchOrderWebhook::class, 'handleConfirmed'],
            ],
            PaymentSucceeded::class => [
                [SendPaymentNotification::class, 'handleSucceeded'],
            ],
            PaymentFailed::class => [
                [SendPaymentNotification::class, 'handleFailed'],
            ],
            PaymentRefunded::class => [
                [SendPaymentNotification::class, 'handleRefunded'],
            ],
        ];
    }
}
