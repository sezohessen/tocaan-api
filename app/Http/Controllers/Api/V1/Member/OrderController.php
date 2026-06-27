<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\ConfirmOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\Actions\Orders\DeleteOrderAction;
use App\Actions\Orders\UpdateOrderAction;
use App\Data\UpdateOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Member Orders
 */
class OrderController extends Controller
{
    /**
     * List my orders
     *
     * @queryParam status string Filter by status (pending, confirmed, cancelled, refunded). Example: confirmed
     * @queryParam sort_by[total] string Sort by a column (asc|desc). Example: desc
     */
    public function index(Request $request, OrderQueryService $orders): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 15);

        return OrderResource::collection(
            $orders->paginateFor($request->user(), $request->all(), $perPage)
        );
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->execute($request->toData());

        return OrderResource::make($order)->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load('items', 'payments'));
    }

    public function update(UpdateOrderRequest $request, Order $order, UpdateOrderAction $action): OrderResource
    {
        $order = $action->execute($order, UpdateOrderData::from($request->validated()));

        return OrderResource::make($order);
    }

    public function destroy(Order $order, DeleteOrderAction $action): JsonResponse
    {
        $action->execute($order);

        return response()->json(status: 204);
    }

    public function confirm(Order $order, ConfirmOrderAction $action): OrderResource
    {
        return OrderResource::make($action->execute($order));
    }

    public function cancel(Order $order, CancelOrderAction $action): OrderResource
    {
        return OrderResource::make($action->execute($order));
    }
}
