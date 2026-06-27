<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class RegisterMemberData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string|Optional|null $phone = null,
    ) {}
}
