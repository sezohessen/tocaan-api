<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Data\RefundPaymentData;
use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
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
            'amount' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.gt' => __('The refund amount must be greater than zero.'),
        ];
    }

    public function toData(): RefundPaymentData
    {
        return new RefundPaymentData(
            amount: $this->filled('amount') ? (float) $this->input('amount') : null,
            reason: $this->input('reason'),
        );
    }
}
