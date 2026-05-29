<?php

namespace App\Services\Telegram\Commands;

use App\Contracts\TelegramCommandContract;
use App\Models\User;

class StartCommand implements TelegramCommandContract
{
    public function name(): string
    {
        return 'start';
    }

    public function description(): string
    {
        return 'Begrüßung und Kurzanleitung.';
    }

    public function execute(User $user, array $args = []): string
    {
        return "Hallo *{$user->name}*! 👋\n\n"
            . "Der ServerFlow-Bot ist mit deinem Account verknüpft. "
            . "Schreibe /help um alle Befehle zu sehen.";
    }
}
