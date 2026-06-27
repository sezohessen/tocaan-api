<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use RuntimeException;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessGatewayWebhookJob extends ProcessWebhookJob
{
    public $queue = 'webhooks';

    public function handle(): void
    {
        $payload = $this->webhookCall->payload;

        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (blank($reference)) {
            throw new RuntimeException('Webhook payload is missing a payment reference.');
        }

        $payment = Payment::query()->where('gateway_reference', $reference)->first();

        if (! $payment) {
            throw new RuntimeException("No payment found for reference [{$reference}].");
        }

        $resolved = PaymentStatus::tryFrom((string) $status);

        if (! $resolved) {
            throw new RuntimeException("Unsupported payment status [{$status}] in webhook payload.");
        }

        $payment->update([
            'status' => $resolved,
            'processed_at' => now(),
        ]);
    }
}
