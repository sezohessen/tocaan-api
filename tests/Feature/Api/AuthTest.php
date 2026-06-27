<?php

declare(strict_types=1);

use App\Events\MemberRegistered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

const AUTH = '/api/v1/member/auth';

it('registers a new member, and fires an event', function () {
    Event::fake([MemberRegistered::class]);

    $response = $this->postJson(AUTH.'/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'access_token', 'token_type', 'expires_in']);

    $this->assertDatabaseHas('members', ['email' => 'jane@example.com']);

    Event::assertDispatched(MemberRegistered::class);
});

it('rejects registration with a duplicate email', function () {
    member(['email' => 'taken@example.com']);

    $this->postJson(AUTH.'/register', [
        'name' => 'Dup',
        'email' => 'taken@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects registration with mismatched password confirmation', function () {
    $this->postJson(AUTH.'/register', [
        'name' => 'Bad',
        'email' => 'bad@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Mismatch123!',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('logs in with valid credentials', function () {
    member(['email' => 'login@example.com', 'password' => Hash::make('Password123!')]);

    $this->postJson(AUTH.'/login', [
        'email' => 'login@example.com',
        'password' => 'Password123!',
    ])->assertOk()->assertJsonStructure(['access_token', 'token_type']);
});

it('rejects login with invalid credentials', function () {
    member(['email' => 'login@example.com', 'password' => Hash::make('Password123!')]);

    $this->postJson(AUTH.'/login', [
        'email' => 'login@example.com',
        'password' => 'wrong',
    ])->assertUnauthorized();
});

it('returns the authenticated member via me', function () {
    $member = actingAsMember();

    $this->getJson(AUTH.'/me')
        ->assertOk()
        ->assertJsonPath('data.email', $member->email);
});

it('blocks unauthenticated access to me', function () {
    $this->getJson(AUTH.'/me')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('logs out the member', function () {
    actingAsMember();

    $this->postJson(AUTH.'/logout')->assertOk();
});
