<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    protected $signature   = 'telegram:set-webhook {url? : Override URL (defaults to APP_URL + route)}';
    protected $description = 'Registriert die Telegram-Webhook-URL beim Bot.';

    public function handle(TelegramService $telegram): int
    {
        $secret = (string) config('serviceboard.telegram.webhook_secret');

        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET ist nicht gesetzt.');
            return self::FAILURE;
        }

        $url = $this->argument('url')
            ?? config('serviceboard.telegram.webhook_url')
            ?? route('telegram.webhook', ['secret' => $secret]);

        $this->line("Setze Webhook auf: <fg=cyan>{$url}</>");

        $result = $telegram->setWebhook($url);

        if ($result['success']) {
            $this->info('Webhook erfolgreich registriert.');
            return self::SUCCESS;
        }

        $this->error('Webhook konnte nicht registriert werden.');
        $this->line(json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::FAILURE;
    }
}
