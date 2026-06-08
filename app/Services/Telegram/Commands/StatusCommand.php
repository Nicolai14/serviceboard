<?php

namespace App\Services\Telegram\Commands;

use App\Contracts\TelegramCommandContract;
use App\Models\Alert;
use App\Models\Server;
use App\Models\User;

class StatusCommand implements TelegramCommandContract
{
    public function name(): string
    {
        return 'status';
    }

    public function description(): string
    {
        return 'Gesamt-Status aller Server.';
    }

    public function execute(User $user, array $args = []): string
    {
        $servers = Server::where('user_id', $user->id)->get();

        if ($servers->isEmpty()) {
            return 'Du hast noch keine Server angelegt.';
        }

        $online  = $servers->where('status', 'online')->count();
        $offline = $servers->where('status', 'offline')->count();
        $other   = $servers->count() - $online - $offline;

        $openAlerts = Alert::where('user_id', $user->id)
            ->unresolved()
            ->count();

        $unread = Alert::where('user_id', $user->id)
            ->unread()
            ->unresolved()
            ->count();

        $lines = [
            '*ServiceBoard Status*',
            '',
            "🖥  Server: *{$servers->count()}* total",
            "    🟢 online: {$online}",
            "    🔴 offline: {$offline}",
        ];

        if ($other > 0) {
            $lines[] = "    ⚪ andere: {$other}";
        }

        $lines[] = '';
        $lines[] = "🚨 Offene Alerts: *{$openAlerts}* ({$unread} ungelesen)";

        return implode("\n", $lines);
    }
}
