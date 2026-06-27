<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderRefund;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderRefund>
 */
class OrderRefundFactory extends Factory
{
    protected $model = OrderRefund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $payment = Payment::factory()->successful();

        return [
            'payment_id' => $payment,
            'order_id' => fn (array $attrs) => Payment::find($attrs['payment_id'])?->order_id,
            'amount' => fake()->randomFloat(2, 5, 100),
            'currency' => 'USD',
            'gateway' => 'credit_card',
            'gateway_reference' => 'cc_re_'.fake()->bothify('########'),
            'reason' => fake()->optional()->sentence(3),
            'processed_at' => now(),
        ];
    }
}
