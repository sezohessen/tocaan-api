<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Member;
use App\Models\Order;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFor(Authenticatable $actor, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::filter($filters)
            ->with('items')
            ->withCount('payments')
            ->latest();

        if ($actor instanceof Member) {
            $query->where('member_id', $actor->getKey());
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
