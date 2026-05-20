<?php

namespace App\Services;

use App\Contracts\NotificationDriverContract;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /** @var NotificationDriverContract[] */
    private array $drivers = [];

    public function registerDriver(NotificationDriverContract $driver): void
    {
        $this->drivers[] = $driver;
    }

    public function dispatch(User $user, string $subject, string $body): void
    {
        $channels = $user->notificationChannels()->where('is_active', true)->get();

        foreach ($channels as $channel) {
            $driver = $this->resolveDriver($channel);

            if ($driver) {
                rescue(fn () => $driver->send($channel, $subject, $body));
            }
        }
    }

    public function dispatchToChannel(NotificationChannel $channel, string $subject, string $body): bool
    {
        $driver = $this->resolveDriver($channel);

        if (! $driver) {
            return false;
        }

        return rescue(fn () => $driver->send($channel, $subject, $body), false);
    }

    public function test(NotificationChannel $channel): array
    {
        $driver = $this->resolveDriver($channel);

        if (! $driver) {
            return ['success' => false, 'message' => 'No driver found for channel type: ' . $channel->type];
        }

        return $driver->test($channel);
    }

    private function resolveDriver(NotificationChannel $channel): ?NotificationDriverContract
    {
        foreach ($this->drivers as $driver) {
            if ($driver->supportsChannel($channel)) {
                return $driver;
            }
        }

        return null;
    }
}
