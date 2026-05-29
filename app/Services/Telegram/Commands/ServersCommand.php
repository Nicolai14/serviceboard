<?php

namespace App\Services\Telegram\Commands;

use App\Contracts\TelegramCommandContract;
use App\Models\Server;
use App\Models\User;

class ServersCommand implements TelegramCommandContract
{
    private const MAX_SERVERS = 20;

    public function name(): string
    {
        return 'servers';
    }

    public function description(): string
    {
        return 'Listet deine Server mit Status.';
    }

    public function execute(User $user, array $args = []): string
    {
        $servers = Server::where('user_id', $user->id)
            ->orderBy('name')
            ->limit(self::MAX_SERVERS + 1)
            ->get();

        if ($servers->isEmpty()) {
            return 'Du hast noch keine Server angelegt.';
        }

        $lines = ['*Deine Server*', ''];

        foreach ($servers->take(self::MAX_SERVERS) as $server) {
            $icon = match ($server->status) {
                'online'  => '🟢',
                'offline' => '🔴',
                default   => '⚪',
            };

            $host = $server->hostname ?: $server->ip_address ?: '—';
            $lines[] = "{$icon} *{$server->name}* — `{$host}`";
        }

        if ($servers->count() > self::MAX_SERVERS) {
            $remaining = $servers->count() - self::MAX_SERVERS;
            $lines[] = '';
            $lines[] = "… und mindestens {$remaining} weitere.";
        }

        return implode("\n", $lines);
    }
}
