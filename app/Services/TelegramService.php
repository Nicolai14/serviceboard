<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    private const API_BASE = 'https://api.telegram.org/bot';

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $token = config('serverflow.telegram.bot_token');

        return Http::baseUrl(self::API_BASE . $token . '/')->acceptJson()->timeout(10);
    }

    public function sendMessage(string $chatId, string $text, string $parseMode = 'Markdown'): bool
    {
        $response = $this->client()->post('sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => $parseMode,
        ]);

        return $response->successful() && $response->json('ok') === true;
    }

    public function testConnection(string $chatId): array
    {
        try {
            $sent = $this->sendMessage($chatId, 'ServerFlow: Test-Nachricht erfolgreich.');

            return [
                'success' => $sent,
                'message' => $sent ? 'Telegram test message sent.' : 'Failed to send message.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
