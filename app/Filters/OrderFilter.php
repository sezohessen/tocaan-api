<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\OrderStatus;

class OrderFilter extends Filter
{
    /**
     * @param  string|array<int, string>  $term
     */
    public function status(string|array $term): void
    {
        $statuses = array_filter(array_map(
            fn (string $status) => OrderStatus::tryFrom($status),
            (array) $term
        ));

        $this->whereIn('status', $statuses);
    }

    public function member(int $memberId): void
    {
        $this->where('member_id', $memberId);
    }

    public function currency(string $currency): void
    {
        $this->where('currency', strtoupper($currency));
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'created_at', 'total', 'status'];
    }
}
