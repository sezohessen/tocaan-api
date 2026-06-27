<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CartStatus;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $uuid
 * @property int $member_id
 * @property CartStatus $status
 * @property string $currency
 * @property int|null $order_id
 * @property-read Collection<int, CartItem> $items
 */
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    use HasUuids;
    use MassPrunable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'member_id',
        'status',
        'currency',
        'order_id',
    ];

    /**
     * Prune abandoned (never checked-out) carts after the configured window.
     *
     * @return Builder<Cart>
     */
    public function prunable(): Builder
    {
        return static::withTrashed()
            ->where('status', '!=', CartStatus::CheckedOut)
            ->where('updated_at', '<=', now()->subDays(
                (int) config('payments.abandoned_cart_ttl_days', 30)
            ));
    }

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
            'status' => CartStatus::class,
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CartStatus::Open;
    }

    public function isEmpty(): bool
    {
        return $this->items()->doesntExist();
    }

    public function subtotal(): float
    {
        return round(
            $this->items->sum(fn (CartItem $item) => $item->lineTotal()),
            2
        );
    }

    public function buildPayment(float $amount): Payment
    {
        return new Payment([
            'amount' => $amount,
            'currency' => $this->currency,
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
