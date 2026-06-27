<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Data\ProcessPaymentData;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentManager;
use App\Payments\Results\ChargeResult;
use Illuminate\Support\Facades\DB;

class ProcessPaymentAction
{
    public function __construct(private readonly PaymentManager $gateways) {}

    public function execute(Order $order, ProcessPaymentData $data): Payment
    {
        $payment = DB::transaction(function () use ($order, $data): Payment {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $this->guardConfirmed($locked);

            $payment = $this->createPendingPayment($locked, $data);

            $result = $this->gateways->driver($data->method->value)->charge($payment);

            $this->applyResult($payment, $result);

            return $payment;
        });

        $this->dispatchResultEvent($payment);

        return $payment;
    }

    private function guardConfirmed(Order $order): void
    {
        if (! $order->isConfirmed()) {
            throw OrderException::notConfirmed($order);
        }
    }

    private function createPendingPayment(Order $order, ProcessPaymentData $data): Payment
    {
        /** @var Payment $payment */
        $payment = $order->payments()->create([
            'status' => PaymentStatus::Pending,
            'gateway' => $data->method->value,
            'method' => $data->method->label(),
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);

        return $payment;
    }

    private function applyResult(Payment $payment, ChargeResult $result): void
    {
        $payment->forceFill([
            'status' => $result->status,
            'gateway_reference' => $result->reference,
            'gateway_response' => $result->rawResponse,
            'processed_at' => now(),
        ])->save();
    }

    private function dispatchResultEvent(Payment $payment): void
    {
        if ($payment->isSuccessful()) {
            event(new PaymentSucceeded($payment));

            return;
        }

        event(new PaymentFailed($payment));
    }
}
