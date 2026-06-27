<?php

declare(strict_types=1);

namespace App\Providers;

use App\Payments\PaymentManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app));
    }

    public function boot(): void
    {
        //
    }
}
