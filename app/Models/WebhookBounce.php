<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\WebhookClient\Models\WebhookCall;

class WebhookBounce extends WebhookCall
{
    protected $table = 'webhook_calls';

    protected static function booted(): void
    {
        static::addGlobalScope('bounced', function (Builder $query): void {
            $query->whereNotNull('exception');
        });
    }
}
