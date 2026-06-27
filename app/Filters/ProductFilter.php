<?php

declare(strict_types=1);

namespace App\Filters;

class ProductFilter extends Filter
{
    public function name(string $term): void
    {
        $this->where('name', 'like', "%{$term}%");
    }

    public function sku(string $term): void
    {
        $this->where('sku', $term);
    }

    public function isActive(bool $active): void
    {
        $this->where('is_active', $active);
    }

    /**
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['id', 'created_at', 'name', 'price'];
    }
}
