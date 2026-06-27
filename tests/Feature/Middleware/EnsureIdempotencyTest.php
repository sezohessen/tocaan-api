<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureIdempotency;
use App\Models\IdempotencyKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
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
        ->and(IdempotencyKey::count())->toBe(1);
});

it('runs the handler again for a different key', function () {
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'k1'])->assertCreated();
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'k2'])->assertCreated();

    expect(IdempotencyKey::count())->toBe(2);
});

it('rejects the same key with a different request body', function () {
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'dup'])->assertCreated();

    $this->postJson('/_test/idempotent', ['a' => 999], ['Idempotency-Key' => 'dup'])
        ->assertStatus(422);
});

it('passes through untouched when no idempotency key is present', function () {
    $this->postJson('/_test/idempotent', ['a' => 1])->assertCreated();
    $this->postJson('/_test/idempotent', ['a' => 1])->assertCreated();

    expect(IdempotencyKey::count())->toBe(0);
});

it('echoes the idempotency key back in the response header', function () {
    $this->postJson('/_test/idempotent', ['a' => 1], ['Idempotency-Key' => 'echo'])
        ->assertHeader('Idempotency-Key', 'echo');
});
