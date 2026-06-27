<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filters\OrderFilter;
use App\Models\Concerns\AuditsDeletions;
use Database\Factories\OrderFactory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $member_id
 * @property OrderStatus $status
 * @property string $currency
 * @property float $subtotal
 * @property float $tax
 * @property float $discount
 * @property float $total
 * @property array<string, mixed>|null $meta
 */
class Order extends Model
{
    use AuditsDeletions;
    use Filterable;

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    public function modelFilter(): string
    {
        return OrderFilter::class;
    }

    protected $fillable = [
        'uuid',
        'member_id',
        'status',
        'currency',
        'subtotal',
        'tax',
        'discount',
        'total',
        'meta',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function hasSuccessfulPayment(): bool
    {
        return $this->payments()->where('status', PaymentStatus::Successful)->exists();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->hasPayments();
    }

    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
