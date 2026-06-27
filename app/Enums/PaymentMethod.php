<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    use BaseEnum;

    case CreditCard = 'credit_card';

    case Paypal = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => __('Credit Card'),
            self::Paypal => __('PayPal'),
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
