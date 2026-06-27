<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    use BaseEnum;

    case Pending = 'pending';

    case Successful = 'successful';

    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Successful => __('Successful'),
            self::Failed => __('Failed'),
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Successful => 'success',
            self::Failed => 'danger',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Successful;
    }
}
