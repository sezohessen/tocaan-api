<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterMemberAction;
use App\Actions\Auth\RegisterUserAction;
use App\Data\RegisterMemberData;
use App\Data\RegisterUserData;
use App\Events\MemberRegistered;
use App\Events\UserRegistered;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

uses(RefreshDatabase::class);

it('registers a member, hashes the password, and fires MemberRegistered', function () {
    Event::fake([MemberRegistered::class]);

    $member = app(RegisterMemberAction::class)->execute(new RegisterMemberData(
        name: 'Jane',
        email: 'jane@example.com',
        password: 'Password123!',
        phone: '+10000000000',
    ));

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->email)->toBe('jane@example.com')
        ->and($member->phone)->toBe('+10000000000')
        ->and(Hash::check('Password123!', $member->password))->toBeTrue();

    $this->assertDatabaseHas('members', ['email' => 'jane@example.com']);
    Event::assertDispatched(MemberRegistered::class, fn ($e) => $e->member->is($member));
});

it('registers an admin user, assigns the role, and fires UserRegistered', function () {
    Event::fake([UserRegistered::class]);

    $user = (new RegisterUserAction('admin'))->execute(new RegisterUserData(
        name: 'Admin',
        email: 'admin@example.com',
        password: 'Password123!',
    ));

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and(Hash::check('Password123!', $user->password))->toBeTrue();

    Event::assertDispatched(UserRegistered::class);
});

it('rolls the user creation back if anything fails inside the transaction', function () {
    expect(fn () => (new RegisterUserAction('nonexistent-role'))->execute(new RegisterUserData(
        name: 'Boom',
        email: 'boom@example.com',
        password: 'Password123!',
    )))->toThrow(RoleDoesNotExist::class);

    $this->assertDatabaseMissing('users', ['email' => 'boom@example.com']);
});
