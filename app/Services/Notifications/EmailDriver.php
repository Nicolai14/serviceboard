<?php

namespace App\Services\Notifications;

use App\Contracts\NotificationDriverContract;
use App\Enums\NotificationChannelType;
use App\Models\NotificationChannel;
use Illuminate\Support\Facades\Mail;

class EmailDriver implements NotificationDriverContract
{
    public function send(NotificationChannel $channel, string $subject, string $body): bool
    {
        $address = $channel->config['address'] ?? null;

        if (! $address) {
            return false;
        }

        Mail::raw($body, function ($msg) use ($address, $subject) {
            $msg->to($address)->subject($subject);
        });

        return true;
    }

    public function test(NotificationChannel $channel): array
    {
        try {
            $sent = $this->send($channel, 'ServerFlow Test', 'Test notification from ServerFlow.');

            return ['success' => $sent, 'message' => $sent ? 'Test email sent.' : 'Missing email address in config.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function supportsChannel(NotificationChannel $channel): bool
    {
        return $channel->type === NotificationChannelType::Email->value;
    }
}
