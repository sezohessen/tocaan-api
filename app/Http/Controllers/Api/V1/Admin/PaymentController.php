<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Payments\RefundPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\RefundPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RefundResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Admin Payments
 */
class PaymentController extends Controller
{
    /**
     * List all payments
     *
     * @queryParam status string Filter by status. Example: successful
     * @queryParam gateway string Filter by gateway. Example: credit_card
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::filter($request->all())
            ->with('order')
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment): PaymentResource
    {
        return PaymentResource::make($payment->load('order'));
    }

    public function forOrder(Order $order): AnonymousResourceCollection
    {
        return PaymentResource::collection($order->payments()->latest()->get());
    }

    public function refund(RefundPaymentRequest $request, Payment $payment, RefundPaymentAction $action): JsonResponse
    {
        $refund = $action->execute($payment, $request->toData());

        return RefundResource::make($refund->load('payment'))->response()->setStatusCode(201);
    }
}
