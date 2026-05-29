<?php

namespace App\Console\Commands;

use App\Services\Telegram\DailyUsageReport;
use Illuminate\Console\Command;

class TelegramDailyReportCommand extends Command
{
    protected $signature   = 'telegram:daily-report';
    protected $description = 'Schickt den täglichen Auslastungs-Report an alle aktiven Telegram-Channels.';

    public function handle(DailyUsageReport $report): int
    {
        $sent = $report->dispatchToAll();

        $this->info("Daily Report an {$sent} Channel(s) versendet.");

        return self::SUCCESS;
    }
}
