<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderRefund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderRefund
 */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'payment_id' => $this->whenLoaded('payment', fn () => $this->payment->uuid),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'reason' => $this->reason,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
