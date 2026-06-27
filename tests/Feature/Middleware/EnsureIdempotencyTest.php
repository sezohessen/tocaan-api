<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureIdempotency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->counter = 0;

    Route::post('/_test/idempotent', function () {
        $this->counter++;

        return response()->json(['count' => $this->counter, 'id' => uniqid()], 201);
    })->middleware(['api', EnsureIdempotency::class]);
});

it('runs the handler once and replays the stored response for the same key', function () {
    $headers = ['Idempotency-Key' => 'abc'];

    $first = $this->postJson('/_test/idempotent', ['a' => 1], $headers)->assertCreated();
    $second = $this->postJson('/_test/idempotent', ['a' => 1], $headers)->assertCreated();

    expect($second->json('id'))->toBe($first->json('id'))
        ->and($this->counter)->toBe(1);
});

it('runs the handler again for a different key', function () {
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'k1'])->assertCreated();
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'k2'])->assertCreated();

    expect($this->counter)->toBe(2);
});

it('rejects the same key with a different request body', function () {
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'dup'])->assertCreated();

    $this->postJson('/_test/idempotent', ['a' => 999], ['Idempotency-Key' => 'dup'])
        ->assertStatus(422);
});

it('passes through untouched when no idempotency key is present', function () {
    $this->postJson('/_test/idempotent', ['a' => 1])->assertCreated();
    $this->postJson('/_test/idempotent', ['a' => 1])->assertCreated();

    expect($this->counter)->toBe(2);
});

it('echoes the idempotency key back in the response header', function () {
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'echo'])
        ->assertHeader('Idempotency-Key', 'echo');
});

it('expires the stored response after the configured ttl', function () {
    config()->set('payments.idempotency_ttl_hours', 1);
    $headers = ['Idempotency-Key' => 'ttl-key'];

    $this->postJson('/_test/idempotent', ['a' => 1], $headers)->assertCreated();

    $this->travel(2)->hours();

    $this->postJson('/_test/idempotent', ['a' => 1], $headers)->assertCreated();

    expect($this->counter)->toBe(2);
});
