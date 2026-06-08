<?php

namespace App\Services\Telegram\Commands;

use App\Contracts\TelegramCommandContract;
use App\Models\User;

class HelpCommand implements TelegramCommandContract
{
    /** @param TelegramCommandContract[] $commands */
    public function __construct(private array $commands = []) {}

    public function name(): string
    {
        return 'help';
    }

    public function description(): string
    {
        return 'Zeigt verfügbare Befehle.';
    }

    public function execute(User $user, array $args = []): string
    {
        $lines = ['*ServiceBoard Bot — Befehle*', ''];

        foreach ($this->commands as $command) {
            $lines[] = sprintf('/%s — %s', $command->name(), $command->description());
        }

        return implode("\n", $lines);
    }
}
