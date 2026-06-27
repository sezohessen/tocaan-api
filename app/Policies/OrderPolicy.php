<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(mixed $authenticatable, string $ability): ?bool
    {
        return $authenticatable instanceof User && $authenticatable->hasRole('admin') ? true : null;
    }

    public function viewAny(Member $member): bool
    {
        return true;
    }

    public function view(Member $member, Order $order): bool
    {
        return $order->member_id === $member->id;
    }

    public function create(Member $member): bool
    {
        return true;
    }

    public function update(Member $member, Order $order): bool
    {
        return $order->member_id === $member->id;
    }

    public function delete(Member $member, Order $order): bool
    {
        return $order->member_id === $member->id;
    }

    public function pay(Member $member, Order $order): bool
    {
        return $order->member_id === $member->id;
    }
}
