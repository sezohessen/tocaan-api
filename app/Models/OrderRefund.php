<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderRefundFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $order_id
 * @property int $payment_id
 * @property float $amount
 * @property string $currency
 * @property string $gateway
 * @property string|null $gateway_reference
 * @property string|null $reason
 * @property array<string, mixed>|null $gateway_response
 * @property Carbon|null $processed_at
 * @property-read Payment $payment
 */
class OrderRefund extends Model
{
    /** @use HasFactory<OrderRefundFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'uuid',
        'order_id',
        'payment_id',
        'amount',
        'currency',
        'gateway',
        'gateway_reference',
        'reason',
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
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
