<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\RegisterMemberData;
use App\Events\MemberRegistered;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class RegisterMemberAction
{
    public function execute(RegisterMemberData $data): Member
    {
        $member = Member::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'phone' => $data->phone,
        ]);

        event(new MemberRegistered($member));

        return $member;
    }
}
