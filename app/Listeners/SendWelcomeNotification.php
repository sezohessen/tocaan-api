<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MemberRegistered;
use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(UserRegistered|MemberRegistered $event): void
    {
        $account = $event instanceof UserRegistered ? $event->user : $event->member;

        Log::info('Account registered', [
            'type' => $event instanceof UserRegistered ? 'user' : 'member',
            'id' => $account->id,
            'email' => $account->email,
        ]);
    }
}
