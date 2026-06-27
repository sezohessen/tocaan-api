<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Member;

use App\Data\RegisterMemberData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:members,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('An account with this email already exists.'),
            'password.confirmed' => __('The password confirmation does not match.'),
        ];
    }

    public function toData(): RegisterMemberData
    {
        return new RegisterMemberData(
            name: (string) $this->validated('name'),
            email: (string) $this->validated('email'),
            password: (string) $this->validated('password'),
            phone: $this->validated('phone'),
        );
    }
}
