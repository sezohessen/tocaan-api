<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Filters\PaymentFilter;
use App\Models\Concerns\AuditsDeletions;
use Database\Factories\PaymentFactory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $order_id
 * @property PaymentStatus $status
 * @property string $gateway
 * @property string|null $method
 * @property float $amount
 * @property string $currency
 * @property string|null $gateway_reference
 * @property array<string, mixed>|null $gateway_response
 * @property Carbon|null $processed_at
 * @property-read Order $order
 */
class Payment extends Model
{
    use AuditsDeletions;
    use Filterable;

    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    public function modelFilter(): string
    {
        return PaymentFilter::class;
    }

    protected $fillable = [
        'uuid',
        'order_id',
        'status',
        'gateway',
        'method',
        'amount',
        'currency',
        'gateway_reference',
        'gateway_response',
        'processed_at',
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
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Successful;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
