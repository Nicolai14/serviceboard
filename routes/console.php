<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every minute: TCP ping → update online/offline status
Schedule::command('servers:check --queue')->everyMinute()->withoutOverlapping();

// Every minute: SSH collect → CPU/RAM/Disk metrics
Schedule::command('servers:metrics --queue')->everyMinute()->withoutOverlapping();

// Every 2 minutes: SSH → docker ps + docker stats
Schedule::command('servers:docker --queue')->everyTwoMinutes()->withoutOverlapping();

// Every minute: HTTP/TCP health checks for configured services (honours per-service interval)
Schedule::command('services:check --queue')->everyMinute()->withoutOverlapping();

// Every 15 minutes: Cloudflare API → zones + DNS records
Schedule::command('cloudflare:sync --queue')->everyFifteenMinutes()->withoutOverlapping();

// Daily 12:00 Berlin: Telegram usage snapshot per user
Schedule::command('telegram:daily-report')->dailyAt('12:00')->timezone('Europe/Berlin');
