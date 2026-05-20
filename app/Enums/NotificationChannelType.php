<?php

namespace App\Enums;

enum NotificationChannelType: string
{
    case Email    = 'email';
    case Telegram = 'telegram';
    case Slack    = 'slack';
    case Webhook  = 'webhook';

    public function label(): string
    {
        return match($this) {
            self::Email    => 'E-Mail',
            self::Telegram => 'Telegram',
            self::Slack    => 'Slack',
            self::Webhook  => 'Webhook',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Email    => 'envelope',
            self::Telegram => 'paper-plane',
            self::Slack    => 'hash',
            self::Webhook  => 'link',
        };
    }

    public function configFields(): array
    {
        return match($this) {
            self::Email    => ['address'],
            self::Telegram => ['chat_id'],
            self::Slack    => ['webhook_url'],
            self::Webhook  => ['url', 'secret'],
        };
    }
}
