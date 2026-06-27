<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CartItem
 */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'unit_price' => $this->whenLoaded('product', fn () => (float) $this->product->price),
            'quantity' => $this->quantity,
            'line_total' => $this->relationLoaded('product') ? $this->lineTotal() : null,
        ];
    }
}
