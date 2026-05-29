<?php

namespace App\Contracts;

use App\Models\User;

interface TelegramCommandContract
{
    public function name(): string;

    public function description(): string;

    public function execute(User $user, array $args = []): string;
}
