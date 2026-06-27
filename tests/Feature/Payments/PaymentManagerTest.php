<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\FakeGateway;
use App\Payments\Gateways\PaypalGateway;
use App\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payments.default', 'credit_card');
    config()->set('payments.gateways.credit_card', [
        'driver' => CreditCardGateway::class,
        'api_key' => 'key',
        'secret' => 'secret',
    ]);
    config()->set('payments.gateways.paypal', [
        'driver' => PaypalGateway::class,
        'client_id' => 'client',
        'secret' => 'secret',
    ]);

    $this->manager = app(PaymentManager::class);
});

it('resolves the default gateway', function () {
    expect($this->manager->driver())->toBeInstanceOf(CreditCardGateway::class);
});

it('resolves a gateway by key', function () {
    expect($this->manager->driver('paypal'))->toBeInstanceOf(PaypalGateway::class)
        ->and($this->manager->driver('credit_card'))->toBeInstanceOf(CreditCardGateway::class);
});

it('throws for an unknown gateway', function () {
    $this->manager->driver('bitcoin');
})->throws(InvalidArgumentException::class);

it('lists available gateways from config', function () {
    expect($this->manager->available())->toContain('credit_card', 'paypal');
});

it('charges through the resolved gateway and returns a successful result', function () {
    $payment = Payment::factory()->create(['amount' => 100, 'gateway' => 'paypal']);

    $result = $this->manager->driver('paypal')->charge($payment);

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->gateway)->toBe('paypal')
        ->and($result->reference)->toStartWith('pp_');
});

it('supports adding a new gateway via config alone', function () {
    config()->set('payments.gateways.custom', [
        'driver' => FakeGateway::class,
        'name' => 'custom',
    ]);

    $gateway = $this->manager->driver('custom');
    $payment = Payment::factory()->create(['amount' => 50]);

    expect($gateway)->toBeInstanceOf(FakeGateway::class)
        ->and($gateway->charge($payment)->isSuccessful())->toBeTrue();
});

it('returns a failed result for a forced gateway failure', function () {
    config()->set('payments.gateways.custom', [
        'driver' => FakeGateway::class,
        'name' => 'custom',
    ]);

    $payment = Payment::factory()->create(['amount' => 50]);

    /** @var FakeGateway $gateway */
    $gateway = $this->manager->driver('custom');
    $result = $gateway->shouldFail('declined')->charge($payment);

    expect($result->isFailed())->toBeTrue()
        ->and($result->message)->toBe('declined');
});
