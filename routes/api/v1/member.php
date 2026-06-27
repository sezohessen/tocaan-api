<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Member\AuthController;
use App\Http\Controllers\Api\V1\Member\CartController;
use App\Http\Controllers\Api\V1\Member\OrderController;
use App\Http\Controllers\Api\V1\Member\PaymentController;
use App\Http\Controllers\Api\V1\Member\ProductController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:member-api')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:member-api')->group(function (): void {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::prefix('cart')->group(function (): void {
        Route::get('/', [CartController::class, 'show']);
        Route::post('items', [CartController::class, 'add']);
        Route::delete('items/{product}', [CartController::class, 'remove']);
        Route::post('checkout', [CartController::class, 'checkout'])->middleware('idempotent');
    });

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store'])->middleware('idempotent')->can('create', Order::class);
    Route::get('orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
    Route::put('orders/{order}', [OrderController::class, 'update'])->can('update', 'order');
    Route::delete('orders/{order}', [OrderController::class, 'destroy'])->can('delete', 'order');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->can('update', 'order');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->can('update', 'order');

    Route::get('orders/{order}/payments', [PaymentController::class, 'forOrder'])->can('view', 'order');
    Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->can('pay', 'order');

    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->can('view', 'payment');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->can('refund', 'payment');
});
