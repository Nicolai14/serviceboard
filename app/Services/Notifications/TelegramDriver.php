<?php

namespace App\Services\Notifications;

use App\Contracts\NotificationDriverContract;
use App\Enums\NotificationChannelType;
use App\Models\NotificationChannel;
use App\Services\TelegramService;

class TelegramDriver implements NotificationDriverContract
{
    public function __construct(private readonly TelegramService $telegram) {}

    public function send(NotificationChannel $channel, string $subject, string $body): bool
    {
        $chatId = $channel->config['chat_id'] ?? null;

        if (! $chatId) {
            return false;
        }

        return $this->telegram->sendMessage($chatId, "*{$subject}*\n\n{$body}");
    }

    public function test(NotificationChannel $channel): array
    {
        $chatId = $channel->config['chat_id'] ?? null;

        if (! $chatId) {
            return ['success' => false, 'message' => 'Missing chat_id in config.'];
        }

        return $this->telegram->testConnection($chatId);
    }

    public function supportsChannel(NotificationChannel $channel): bool
    {
        return $channel->type === NotificationChannelType::Telegram->value;
    }
}
