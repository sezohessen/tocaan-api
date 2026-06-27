<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Admin Orders
 */
class OrderController extends Controller
{
    /**
     * List all orders
     *
     * @queryParam status string Filter by status. Example: confirmed
     * @queryParam sort_by[total] string Sort by a column (asc|desc). Example: desc
     */
    public function index(Request $request, OrderQueryService $orders): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 15);

        return OrderResource::collection(
            $orders->paginateFor($request->user(), $request->all(), $perPage)
        );
    }

    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load('items', 'payments'));
    }
}
