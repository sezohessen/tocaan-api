<?php

declare(strict_types=1);

namespace App\Filters;

use Carbon\Carbon;
use EloquentFilter\ModelFilter;

abstract class Filter extends ModelFilter
{
    public function id(int $id): void
    {
        $this->where('id', $id);
    }

    public function fromDate(string $date): void
    {
        $this->whereDate('created_at', '>=', Carbon::parse($date));
    }

    public function toDate(string $date): void
    {
        $this->whereDate('created_at', '<=', Carbon::parse($date));
    }

    /**
     * @param  array<string, string>  $order
     */
    public function sortBy(array $order): void
    {
        foreach ($order as $column => $direction) {
            $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

            if (in_array($column, $this->sortable(), true)) {
                $this->orderBy($column, $direction);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'created_at'];
    }
}
