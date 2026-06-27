<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\PaymentEventSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string>
     */
    protected array $subscribe = [
        PaymentEventSubscriber::class,
    ];

    public function boot(): void
    {
        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }
    }
}
