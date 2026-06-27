<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CartStatus: string implements HasColor, HasLabel
{
    use BaseEnum;

    case Open = 'open';

    case CheckedOut = 'checked_out';

    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::CheckedOut => __('Checked Out'),
            self::Abandoned => __('Abandoned'),
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Open => 'primary',
            self::CheckedOut => 'success',
            self::Abandoned => 'gray',
        };
    }
}
