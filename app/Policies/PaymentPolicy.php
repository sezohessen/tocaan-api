<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(mixed $authenticatable, string $ability): ?bool
    {
        return $authenticatable instanceof User && $authenticatable->hasRole('admin') ? true : null;
    }

    public function viewAny(Member $member): bool
    {
        return true;
    }

    public function view(Member $member, Payment $payment): bool
    {
        return $payment->order->member_id === $member->id;
    }
}
