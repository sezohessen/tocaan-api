<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\RegisterUserData;
use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function __construct(private readonly string $defaultRole = 'customer') {}

    public function execute(RegisterUserData $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $user->assignRole($this->defaultRole);

            return $user;
        });

        event(new UserRegistered($user));

        return $user;
    }
}
