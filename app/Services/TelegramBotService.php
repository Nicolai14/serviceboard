<?php

namespace App\Services;

use App\Contracts\TelegramCommandContract;
use App\Enums\NotificationChannelType;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    /** @var array<string, TelegramCommandContract> */
    private array $commands = [];

    public function __construct(private readonly TelegramService $telegram) {}

    public function registerCommand(TelegramCommandContract $command): void
    {
        $this->commands[strtolower($command->name())] = $command;
    }

    /** @return array<string, TelegramCommandContract> */
    public function commands(): array
    {
        return $this->commands;
    }

    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (! $message) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text   = trim((string) ($message['text'] ?? ''));

        if ($chatId === '' || $text === '') {
            return;
        }

        if (! str_starts_with($text, '/')) {
            return;
        }

        $user = $this->resolveUser($chatId);

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "Diese Chat-ID ist keinem ServiceBoard-Account zugeordnet.\n\n"
                . "Lege in der Web-UI einen Telegram-Notification-Channel mit folgender Chat-ID an:\n"
                . "`{$chatId}`",
            );

            return;
        }

        [$commandName, $args] = $this->parseCommand($text);
        $command = $this->commands[$commandName] ?? null;

        if (! $command) {
            $this->telegram->sendMessage(
                $chatId,
                "Unbekannter Befehl `/{$commandName}`. Schreibe /help für eine Übersicht.",
            );

            return;
        }

        try {
            $reply = $command->execute($user, $args);
        } catch (\Throwable $e) {
            Log::error('Telegram command failed', [
                'command' => $commandName,
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            $reply = '⚠️ Beim Ausführen des Befehls ist ein Fehler aufgetreten.';
        }

        $this->telegram->sendMessage($chatId, $reply);
    }

    private function resolveUser(string $chatId): ?User
    {
        $channels = NotificationChannel::query()
            ->where('type', NotificationChannelType::Telegram->value)
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            if ((string) ($channel->config['chat_id'] ?? '') === $chatId) {
                return $channel->user;
            }
        }

        return null;
    }

    /** @return array{0: string, 1: array<int, string>} */
    private function parseCommand(string $text): array
    {
        $parts = preg_split('/\s+/', $text) ?: [];
        $head  = ltrim((string) array_shift($parts), '/');

        if (str_contains($head, '@')) {
            $head = explode('@', $head, 2)[0];
        }

        return [strtolower($head), $parts];
    }
}
