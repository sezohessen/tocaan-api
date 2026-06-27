<?php

declare(strict_types=1);

use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        $this->seed(RolesSeeder::class);
    })
    ->in('Feature');

/**
 * Create an admin User with the given role.
 */
function adminUser(string $role = 'admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * Create a Member (customer).
 */
function member(array $attributes = []): Member
{
    return Member::factory()->create($attributes);
}

/**
 * @return array<string, string>
 */
function bearer(string $token): array
{
    return ['Authorization' => 'Bearer '.$token];
}

/**
 * Act as a Member on the member-api guard.
 */
function actingAsMember(?Member $member = null): Member
{
    $member ??= member();

    test()->withHeaders(bearer(JWTAuth::fromUser($member)));

    return $member;
}

/**
 * Act as an admin User on the api guard.
 */
function actingAsAdmin(?User $user = null): User
{
    $user ??= adminUser();

    test()->withHeaders(bearer(JWTAuth::fromUser($user)));

    return $user;
}
