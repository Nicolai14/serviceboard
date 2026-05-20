<?php

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannelType;
use PHPUnit\Framework\TestCase;

class NotificationChannelTypeTest extends TestCase
{
    public function test_email_config_fields(): void
    {
        $this->assertSame(['address'], NotificationChannelType::Email->configFields());
    }

    public function test_telegram_config_fields(): void
    {
        $this->assertSame(['chat_id'], NotificationChannelType::Telegram->configFields());
    }

    public function test_slack_config_fields(): void
    {
        $this->assertSame(['webhook_url'], NotificationChannelType::Slack->configFields());
    }

    public function test_webhook_config_fields(): void
    {
        $this->assertSame(['url', 'secret'], NotificationChannelType::Webhook->configFields());
    }

    public function test_labels_are_non_empty(): void
    {
        foreach (NotificationChannelType::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }
    }

    public function test_from_string_values(): void
    {
        $this->assertSame(NotificationChannelType::Email,    NotificationChannelType::from('email'));
        $this->assertSame(NotificationChannelType::Telegram, NotificationChannelType::from('telegram'));
        $this->assertSame(NotificationChannelType::Slack,    NotificationChannelType::from('slack'));
        $this->assertSame(NotificationChannelType::Webhook,  NotificationChannelType::from('webhook'));
    }
}
