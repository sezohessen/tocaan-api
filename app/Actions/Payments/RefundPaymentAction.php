<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Data\RefundPaymentData;
use App\Enums\OrderStatus;
use App\Events\PaymentRefunded;
use App\Exceptions\RefundException;
use App\Models\OrderRefund;
use App\Models\Payment;
use App\Payments\Contracts\Refundable;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class RefundPaymentAction
{
    public function __construct(private readonly PaymentManager $gateways) {}

    public function execute(Payment $payment, RefundPaymentData $data): OrderRefund
    {
        $refund = DB::transaction(function () use ($payment, $data): OrderRefund {
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $gateway = $this->resolveRefundableGateway($locked);
            $amount = $this->resolveAmount($locked, $data);

            $result = $gateway->refund($locked, $amount);

            if (! $result->isSuccessful()) {
                throw RefundException::gatewayDeclined($result->message);
            }

            /** @var OrderRefund $refund */
            $refund = $locked->refunds()->create([
                'order_id' => $locked->order_id,
                'amount' => $amount,
                'currency' => $locked->currency,
                'gateway' => $result->gateway,
                'gateway_reference' => $result->reference,
                'reason' => $data->reason instanceof Optional ? null : $data->reason,
                'gateway_response' => $result->rawResponse,
                'processed_at' => now(),
            ]);

            $this->markOrderRefundedIfComplete($locked);

            return $refund;
        });

        $payment->refresh();

        event(new PaymentRefunded($payment, $refund, $payment->isFullyRefunded()));

        return $refund;
    }

    private function resolveRefundableGateway(Payment $payment): Refundable
    {
        if (! $payment->isSuccessful()) {
            throw RefundException::notSuccessful($payment);
        }

        $gateway = $this->gateways->driver($payment->gateway);

        if (! $gateway instanceof Refundable) {
            throw RefundException::gatewayNotRefundable($payment->gateway);
        }

        return $gateway;
    }

    private function resolveAmount(Payment $payment, RefundPaymentData $data): float
    {
        $refundable = $payment->refundableAmount();

        $amount = ($data->amount instanceof Optional || $data->amount === null)
            ? $refundable
            : round((float) $data->amount, 2);

        if ($amount <= 0 || $amount > $refundable) {
            throw RefundException::exceedsRefundable($payment);
        }

        return $amount;
    }

    private function markOrderRefundedIfComplete(Payment $payment): void
    {
        if (! $payment->fresh()->isFullyRefunded()) {
            return;
        }

        $order = $payment->order;

        if ($order->status->canTransitionTo(OrderStatus::Refunded)) {
            $order->update(['status' => OrderStatus::Refunded]);
        }
    }
}
