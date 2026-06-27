<?php

declare(strict_types=1);

use App\Exceptions\CartException;
use App\Models\Product;
use App\Services\Inventory\InventoryManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('reserves stock for available products', function () {
    $a = Product::factory()->create(['stock' => 10]);
    $b = Product::factory()->create(['stock' => 5]);

    DB::transaction(fn () => app(InventoryManager::class)->reserve([
        $a->id => 3,
        $b->id => 2,
    ]));

    expect($a->refresh()->stock)->toBe(7)
        ->and($b->refresh()->stock)->toBe(3);
});

it('throws and reserves nothing when any product is short on stock', function () {
    $a = Product::factory()->create(['stock' => 10]);
    $b = Product::factory()->create(['stock' => 1]);

    expect(fn () => DB::transaction(fn () => app(InventoryManager::class)->reserve([
        $a->id => 3,
        $b->id => 5, // exceeds stock
    ])))->toThrow(CartException::class);

    // The whole transaction rolls back: neither product was decremented.
    expect($a->refresh()->stock)->toBe(10)
        ->and($b->refresh()->stock)->toBe(1);
});

it('throws for an inactive product', function () {
    $product = Product::factory()->inactive()->create(['stock' => 10]);

    expect(fn () => DB::transaction(fn () => app(InventoryManager::class)->reserve([$product->id => 1])))
        ->toThrow(CartException::class);
});

it('reserves combined quantity for a repeated product id', function () {
    $product = Product::factory()->create(['stock' => 5]);

    DB::transaction(fn () => app(InventoryManager::class)->reserve([$product->id => 5]));

    expect($product->refresh()->stock)->toBe(0);
});
