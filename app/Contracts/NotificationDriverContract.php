<?php

namespace App\Contracts;

use App\Models\NotificationChannel;

interface NotificationDriverContract
{
    public function send(NotificationChannel $channel, string $subject, string $body): bool;

    public function test(NotificationChannel $channel): array;

    public function supportsChannel(NotificationChannel $channel): bool;
}
