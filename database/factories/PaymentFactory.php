<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => PaymentStatus::Pending,
            'gateway' => PaymentMethod::CreditCard->value,
            'method' => PaymentMethod::CreditCard->label(),
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'USD',
            'gateway_reference' => null,
            'gateway_response' => null,
            'processed_at' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Successful,
            'gateway_reference' => 'ref_'.fake()->uuid(),
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed,
            'processed_at' => now(),
        ]);
    }
}
