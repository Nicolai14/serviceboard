<?php

namespace App\Services\Telegram\Commands;

use App\Contracts\TelegramCommandContract;
use App\Models\Alert;
use App\Models\User;

class AlertsCommand implements TelegramCommandContract
{
    private const MAX_ALERTS = 10;

    public function name(): string
    {
        return 'alerts';
    }

    public function description(): string
    {
        return 'Zeigt offene Alerts (max. 10).';
    }

    public function execute(User $user, array $args = []): string
    {
        $alerts = Alert::with('server')
            ->where('user_id', $user->id)
            ->unresolved()
            ->latest()
            ->limit(self::MAX_ALERTS)
            ->get();

        if ($alerts->isEmpty()) {
            return '✅ Keine offenen Alerts.';
        }

        $lines = ['*Offene Alerts*', ''];

        foreach ($alerts as $alert) {
            $icon = match ($alert->severity) {
                'critical' => '🔴',
                'warning'  => '🟠',
                default    => '🔵',
            };

            $server = $alert->server->name ?? 'Unbekannt';
            $when   = $alert->created_at?->diffForHumans() ?? '';
            $lines[] = "{$icon} *{$server}* — {$alert->message}";
            $lines[] = "    _{$when}_";
        }

        return implode("\n", $lines);
    }
}
