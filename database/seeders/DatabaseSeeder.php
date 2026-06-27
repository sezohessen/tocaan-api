<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@tocaan.test',
        ]);
        $admin->assignRole('admin');

        $member = Member::factory()->create([
            'name' => 'Customer',
            'email' => 'customer@tocaan.test',
        ]);

        $products = Product::factory()->count(10)->create();

        Order::factory()
            ->confirmed()
            ->for($member)
            ->has(
                OrderItem::factory()
                    ->count(2)
                    ->state(fn () => ['product_id' => $products->random()->id]),
                'items'
            )
            ->has(Payment::factory()->successful(), 'payments')
            ->count(3)
            ->create();

        Order::factory()
            ->pending()
            ->for($member)
            ->has(
                OrderItem::factory()
                    ->state(fn () => ['product_id' => $products->random()->id]),
                'items'
            )
            ->count(2)
            ->create();
    }
}
