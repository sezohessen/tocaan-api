<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Order;

use App\Data\CreateOrderData;
use App\Data\OrderItemData;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\DataCollection;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'currency' => ['sometimes', 'string', 'size:3'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('An order must contain at least one item.'),
            'items.min' => __('An order must contain at least one item.'),
            'items.*.product_id.exists' => __('The selected product does not exist.'),
            'items.*.quantity.min' => __('Item quantity must be at least 1.'),
        ];
    }

    public function toData(): CreateOrderData
    {
        return new CreateOrderData(
            memberId: (int) $this->user()->getKey(),
            currency: strtoupper((string) $this->input('currency', 'USD')),
            items: OrderItemData::collect($this->input('items'), DataCollection::class),
            tax: (float) $this->input('tax', 0),
            discount: (float) $this->input('discount', 0),
            meta: $this->input('meta'),
        );
    }
}
