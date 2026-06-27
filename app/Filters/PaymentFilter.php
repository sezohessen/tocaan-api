<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\PaymentStatus;

class PaymentFilter extends Filter
{
    /**
     * @param  string|array<int, string>  $term
     */
    public function status(string|array $term): void
    {
        $statuses = array_filter(array_map(
            fn (string $status) => PaymentStatus::tryFrom($status),
            (array) $term
        ));

        $this->whereIn('status', $statuses);
    }

    public function gateway(string $gateway): void
    {
        $this->where('gateway', $gateway);
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'created_at', 'amount', 'status'];
    }
}
