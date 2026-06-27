<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'status' => CartStatus::Open,
            'currency' => 'USD',
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn () => ['status' => CartStatus::CheckedOut]);
    }
}
