<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookBounce;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                __('tocaan.widgets.orders_total'),
                (string) Order::query()->count()
            )->color('primary'),

            Stat::make(
                __('tocaan.widgets.revenue'),
                number_format((float) Payment::query()->where('status', PaymentStatus::Successful)->sum('amount'), 2)
            )->color('success'),

            Stat::make(
                __('tocaan.widgets.failed_payments'),
                (string) Payment::query()->where('status', PaymentStatus::Failed)->count()
            )->color('danger'),

            Stat::make(
                __('tocaan.widgets.webhook_bounces'),
                (string) WebhookBounce::query()->count()
            )->color('warning'),
        ];
    }
}
