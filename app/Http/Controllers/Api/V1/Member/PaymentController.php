<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\Payments\ProcessPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Member;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Member Payments
 */
class PaymentController extends Controller
{
    /**
     * List my payments
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Member $member */
        $member = $request->user();

        $payments = Payment::filter($request->all())
            ->with('order')
            ->whereHas('order', fn ($q) => $q->where('member_id', $member->getKey()))
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }

    public function forOrder(Order $order): AnonymousResourceCollection
    {
        return PaymentResource::collection($order->payments()->latest()->get());
    }

    public function store(ProcessPaymentRequest $request, Order $order, ProcessPaymentAction $action): JsonResponse
    {
        $payment = $action->execute($order, $request->toData());

        return PaymentResource::make($payment->load('order'))->response()->setStatusCode(200);
    }

    public function show(Payment $payment): PaymentResource
    {
        return PaymentResource::make($payment->load('order'));
    }
}
