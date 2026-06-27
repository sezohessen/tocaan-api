<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::webhooks('webhooks/payments');
