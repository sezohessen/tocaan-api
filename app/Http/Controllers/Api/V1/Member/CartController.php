<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Cart\AddToCartAction;
use App\Actions\Cart\CheckoutCartAction;
use App\Actions\Cart\RemoveFromCartAction;
use App\Enums\CartStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddToCartRequest;
use App\Http\Requests\Api\V1\Cart\CheckoutCartRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Member Cart
 */
class CartController extends Controller
{
    /**
     * Get current cart
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->member($request)->carts()->firstOrCreate(
            ['status' => CartStatus::Open],
            ['currency' => config('app.currency', 'USD')]
        );

        return CartResource::make($cart->load('items.product'))->response()->setStatusCode(200);
    }

    /**
     * Add item to cart
     *
     * @bodyParam product_id integer required An existing product id. Example: 1
     * @bodyParam quantity integer required Quantity to add. Example: 2
     */
    public function add(AddToCartRequest $request, AddToCartAction $action): JsonResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));

        $cart = $action->execute($this->member($request), $product, (int) $request->validated('quantity'));

        return CartResource::make($cart)->response()->setStatusCode(200);
    }

    public function remove(Request $request, Product $product, RemoveFromCartAction $action): JsonResponse
    {
        /** @var Cart $cart */
        $cart = $this->member($request)->carts()->where('status', CartStatus::Open)->firstOrFail();

        return CartResource::make($action->execute($cart, $product))->response()->setStatusCode(200);
    }

    /**
     * Checkout the cart
     *
     * @header Idempotency-Key A unique key to safely retry checkout without double-charging. Example: 1f9c2d3e-4b5a-6c7d-8e9f-0a1b2c3d4e5f
     */
    public function checkout(CheckoutCartRequest $request, CheckoutCartAction $action): JsonResponse
    {
        /** @var Cart $cart */
        $cart = $this->member($request)->carts()->where('status', CartStatus::Open)->firstOrFail();

        $order = $action->execute($cart, $request->toData(), [
            'tax' => (float) $request->input('tax', 0),
            'discount' => (float) $request->input('discount', 0),
        ]);

        return OrderResource::make($order)->response()->setStatusCode(201);
    }

    private function member(Request $request): Member
    {
        /** @var Member $member */
        $member = $request->user();

        return $member;
    }
}
