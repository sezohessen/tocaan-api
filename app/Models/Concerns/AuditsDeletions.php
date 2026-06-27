<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait AuditsDeletions
{
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected static $recordEvents = ['deleted'];

    public static function bootAuditsDeletions(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting() && method_exists($model, 'disableLogging')) {
                $model->disableLogging();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        $sensitive = array_values(array_unique(array_merge($this->getHidden(), [
            'password',
            'remember_token',
            'secret',
            'token',
        ])));

        return LogOptions::defaults()
            ->useLogName('audit')
            ->logAll()
            ->logExcept($sensitive)
            ->setDescriptionForEvent(fn (string $eventName): string => class_basename(static::class).'.'.$eventName);
    }
}
