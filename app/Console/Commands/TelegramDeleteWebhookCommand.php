<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramDeleteWebhookCommand extends Command
{
    protected $signature   = 'telegram:delete-webhook';
    protected $description = 'Entfernt die registrierte Telegram-Webhook-URL.';

    public function handle(TelegramService $telegram): int
    {
        $result = $telegram->deleteWebhook();

        if ($result['success']) {
            $this->info('Webhook entfernt.');
            return self::SUCCESS;
        }

        $this->error('Konnte Webhook nicht entfernen.');
        $this->line(json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::FAILURE;
    }
}
