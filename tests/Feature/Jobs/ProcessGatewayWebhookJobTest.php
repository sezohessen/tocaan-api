<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Jobs\ProcessGatewayWebhookJob;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\WebhookClient\Models\WebhookCall;

uses(RefreshDatabase::class);

function webhookCall(array $payload): WebhookCall
{
    return WebhookCall::create([
        'name' => 'default',
        'url' => 'https://tocaan.test/api/v1/webhooks/payments',
        'payload' => $payload,
    ]);
}

it('reconciles a payment status from the webhook payload', function () {
    $payment = Payment::factory()->create([
        'gateway_reference' => 'ref_abc',
        'status' => PaymentStatus::Pending,
    ]);

    (new ProcessGatewayWebhookJob(webhookCall([
        'reference' => 'ref_abc',
        'status' => 'successful',
    ])))->handle();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Successful)
        ->and($payment->processed_at)->not->toBeNull();
});

it('throws when the payload is missing a reference (becomes a bounce)', function () {
    expect(fn () => (new ProcessGatewayWebhookJob(webhookCall(['status' => 'successful'])))->handle())
        ->toThrow(RuntimeException::class, 'missing a payment reference');
});

it('throws when no payment matches the reference', function () {
    expect(fn () => (new ProcessGatewayWebhookJob(webhookCall([
        'reference' => 'does-not-exist',
        'status' => 'successful',
    ])))->handle())->toThrow(RuntimeException::class, 'No payment found');
});

it('throws for an unsupported status value', function () {
    Payment::factory()->create(['gateway_reference' => 'ref_xyz']);

    expect(fn () => (new ProcessGatewayWebhookJob(webhookCall([
        'reference' => 'ref_xyz',
        'status' => 'bogus',
    ])))->handle())->toThrow(RuntimeException::class, 'Unsupported payment status');
});

it('queues onto the webhooks queue', function () {
    expect((new ProcessGatewayWebhookJob(webhookCall(['reference' => 'r', 'status' => 'failed'])))->queue)
        ->toBe('webhooks');
});
