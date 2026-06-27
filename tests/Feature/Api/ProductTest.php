<?php

declare(strict_types=1);

use App\Models\Product;

const ADMIN_PRODUCTS = '/api/v1/admin/products';

const MEMBER_PRODUCTS = '/api/v1/member/products';

it('lists active products for a member', function () {
    actingAsMember();
    Product::factory()->count(3)->create();
    Product::factory()->inactive()->create();

    $this->getJson(MEMBER_PRODUCTS)
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'sku', 'price', 'stock']], 'meta', 'links']);
});

it('shows admins inactive products too', function () {
    actingAsAdmin();
    Product::factory()->count(2)->create();
    Product::factory()->inactive()->create();

    $this->getJson(ADMIN_PRODUCTS)->assertOk()->assertJsonCount(3, 'data');
});

it('filters products by name', function () {
    actingAsMember();
    Product::factory()->create(['name' => 'Mechanical Keyboard']);
    Product::factory()->create(['name' => 'Wireless Mouse']);

    $this->getJson(MEMBER_PRODUCTS.'?name=Keyboard')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mechanical Keyboard');
});

it('lets an admin create a product', function () {
    actingAsAdmin();

    $this->postJson(ADMIN_PRODUCTS, [
        'name' => 'New Product',
        'sku' => 'SKU-NEW-1',
        'price' => 99.99,
        'stock' => 25,
    ])->assertCreated()->assertJsonPath('data.sku', 'SKU-NEW-1');

    $this->assertDatabaseHas('products', ['sku' => 'SKU-NEW-1']);
});

it('forbids a manager without products.manage from creating a product', function () {
    actingAsAdmin(adminUser('manager'));

    $this->postJson(ADMIN_PRODUCTS, [
        'name' => 'Nope',
        'sku' => 'SKU-NOPE',
        'price' => 10,
        'stock' => 1,
    ])->assertForbidden();
});

it('validates product creation input', function () {
    actingAsAdmin();

    $this->postJson(ADMIN_PRODUCTS, ['price' => -5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'sku', 'stock', 'price']);
});

it('lets an admin update a product', function () {
    actingAsAdmin();
    $product = Product::factory()->create(['price' => 10]);

    $this->putJson(ADMIN_PRODUCTS.'/'.$product->id, ['price' => 49.5])
        ->assertOk()
        ->assertJsonPath('data.price', 49.5);
});

it('lets an admin delete a product', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->deleteJson(ADMIN_PRODUCTS.'/'.$product->id)->assertNoContent();
    $this->assertSoftDeleted('products', ['id' => $product->id]);
});
