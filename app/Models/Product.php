<?php

declare(strict_types=1);

namespace App\Models;

use App\Filters\ProductFilter;
use App\Models\Concerns\AuditsDeletions;
use Database\Factories\ProductFactory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property float $price
 * @property string $currency
 * @property int $stock
 * @property bool $is_active
 */
class Product extends Model
{
    use AuditsDeletions;
    use Filterable;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    public function modelFilter(): string
    {
        return ProductFilter::class;
    }

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'currency',
        'stock',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isAvailable(int $quantity = 1): bool
    {
        return $this->is_active && $this->stock >= $quantity;
    }
}
