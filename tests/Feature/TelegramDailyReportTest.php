<?php

namespace Tests\Feature;

use App\Models\Metric;
use App\Models\NotificationChannel;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramDailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'serviceboard.telegram.bot_token' => 'test-bot-token',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);
    }

    public function test_daily_report_sends_photo_with_chart_url_when_metrics_exist(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '111');

        $server = Server::factory()->create(['user_id' => $user->id, 'name' => 'web-01']);
        Metric::create([
            'server_id'      => $server->id,
            'cpu_usage'      => 42.5,
            'memory_usage'   => 4_000_000_000,
            'memory_total'   => 8_000_000_000,
            'disk_usage'     => 50_000_000_000,
            'disk_total'     => 100_000_000_000,
            'load_average'   => 0.5,
            'uptime_seconds' => 1000,
            'recorded_at'    => now(),
        ]);

        $this->artisan('telegram:daily-report')->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendPhoto')
                && str_contains((string) $request['photo'], 'quickchart.io/chart')
                && str_contains((string) $request['caption'], 'web-01')
                && str_contains((string) $request['caption'], '42.5%');
        });
    }

    public function test_daily_report_falls_back_to_text_when_no_metrics_exist(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '222');
        Server::factory()->create(['user_id' => $user->id, 'name' => 'lonely-server']);

        $this->artisan('telegram:daily-report')->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains((string) $request['text'], 'lonely-server')
                && str_contains((string) $request['text'], 'keine Daten');
        });
    }

    public function test_user_without_servers_gets_friendly_message(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '333');

        $this->artisan('telegram:daily-report')->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains((string) $request['text'], 'noch keine Server');
        });
    }

    public function test_inactive_channels_are_skipped(): void
    {
        $user = User::factory()->create();
        NotificationChannel::create([
            'user_id'   => $user->id,
            'name'      => 'Telegram',
            'type'      => 'telegram',
            'config'    => ['chat_id' => '444'],
            'is_active' => false,
        ]);

        $this->artisan('telegram:daily-report')->assertSuccessful();

        Http::assertNothingSent();
    }

    private function linkTelegram(User $user, string $chatId): NotificationChannel
    {
        return NotificationChannel::create([
            'user_id'   => $user->id,
            'name'      => 'Telegram',
            'type'      => 'telegram',
            'config'    => ['chat_id' => $chatId],
            'is_active' => true,
        ]);
    }
}
