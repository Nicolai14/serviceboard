<?php

namespace App\Services\Telegram;

use App\Enums\NotificationChannelType;
use App\Models\NotificationChannel;
use App\Models\Server;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class DailyUsageReport
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly UsageChartBuilder $chartBuilder,
    ) {}

    /** @return int Number of channels the report was sent to. */
    public function dispatchToAll(): int
    {
        $channels = NotificationChannel::query()
            ->where('type', NotificationChannelType::Telegram->value)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($channels as $channel) {
            if ($this->sendToChannel($channel)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function sendToChannel(NotificationChannel $channel): bool
    {
        $user   = $channel->user;
        $chatId = (string) ($channel->config['chat_id'] ?? '');

        if (! $user || $chatId === '') {
            return false;
        }

        $servers = Server::where('user_id', $user->id)->orderBy('name')->get();

        if ($servers->isEmpty()) {
            return $this->telegram->sendMessage(
                $chatId,
                "📊 *Tagesreport*\n\nDu hast noch keine Server angelegt.",
            );
        }

        $result  = $this->chartBuilder->buildSnapshot($servers);
        $caption = $this->buildCaption($result['rows']);

        try {
            if ($result['url'] !== null) {
                return $this->telegram->sendPhoto($chatId, $result['url'], $caption);
            }

            return $this->telegram->sendMessage($chatId, $caption);
        } catch (\Throwable $e) {
            Log::warning('Telegram daily report failed', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @param array<int, array{name: string, cpu: ?float, ram: ?float, disk: ?float, recorded_at: ?string}> $rows */
    private function buildCaption(array $rows): string
    {
        $lines = ['📊 *Tagesreport — Server-Auslastung*', ''];

        foreach ($rows as $row) {
            if ($row['cpu'] === null && $row['ram'] === null && $row['disk'] === null) {
                $lines[] = "• *{$row['name']}* — _keine Daten_";
                continue;
            }

            $cpu  = $this->fmt($row['cpu']);
            $ram  = $this->fmt($row['ram']);
            $disk = $this->fmt($row['disk']);

            $lines[] = "• *{$row['name']}* — CPU {$cpu} · RAM {$ram} · Disk {$disk}";
        }

        return implode("\n", $lines);
    }

    private function fmt(?float $value): string
    {
        return $value === null ? '—' : number_format($value, 1) . '%';
    }
}
