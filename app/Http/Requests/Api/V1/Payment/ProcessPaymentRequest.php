<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payment;

use App\Data\ProcessPaymentData;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
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
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'details' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.required' => __('A payment method is required.'),
            'method.Illuminate\Validation\Rules\Enum' => __('The selected payment method is not supported.'),
        ];
    }

    public function toData(): ProcessPaymentData
    {
        return ProcessPaymentData::from([
            'method' => $this->input('method'),
            'details' => $this->input('details'),
        ]);
    }
}
