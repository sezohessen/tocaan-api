<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $scope
 * @property string $request_fingerprint
 * @property int|null $response_status
 * @property array<string, mixed>|null $response_body
 */
class IdempotencyKey extends Model
{
    use MassPrunable;

    protected $fillable = [
        'key',
        'scope',
        'request_fingerprint',
        'response_status',
        'response_body',
    ];

    /**
     * @return Builder<IdempotencyKey>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(
            (int) config('payments.idempotency_ttl_days', 7)
        ));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_body' => 'array',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->response_status !== null;
    }
}
