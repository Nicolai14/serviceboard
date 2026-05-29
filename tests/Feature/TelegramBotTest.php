<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\NotificationChannel;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'serverflow.telegram.bot_token'      => 'test-bot-token',
            'serverflow.telegram.webhook_secret' => 'test-secret',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);
    }

    public function test_webhook_rejects_invalid_secret(): void
    {
        $this->postJson('/api/telegram/webhook/wrong-secret', [])
            ->assertNotFound();
    }

    public function test_webhook_accepts_valid_secret(): void
    {
        $this->postJson('/api/telegram/webhook/test-secret', [])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_status_command_returns_server_overview(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '123456');

        Server::factory()->create(['user_id' => $user->id, 'status' => 'online']);
        Server::factory()->create(['user_id' => $user->id, 'status' => 'offline']);

        $this->postJson('/api/telegram/webhook/test-secret', $this->update('/status', '123456'))
            ->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Server: *2*')
                && str_contains($request['text'], 'online: 1')
                && str_contains($request['text'], 'offline: 1');
        });
    }

    public function test_servers_command_lists_user_servers(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '789');

        Server::factory()->create(['user_id' => $user->id, 'name' => 'web-01', 'hostname' => 'web.example.com', 'status' => 'online']);
        Server::factory()->create(['user_id' => $user->id, 'name' => 'db-01',  'hostname' => 'db.example.com',  'status' => 'offline']);

        $this->postJson('/api/telegram/webhook/test-secret', $this->update('/servers', '789'))
            ->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'web-01')
                && str_contains($request['text'], 'db-01');
        });
    }

    public function test_alerts_command_lists_open_alerts(): void
    {
        $user   = User::factory()->create();
        $this->linkTelegram($user, '999');
        $server = Server::factory()->create(['user_id' => $user->id, 'name' => 'web-01']);

        Alert::factory()->create([
            'user_id'   => $user->id,
            'server_id' => $server->id,
            'message'   => 'Container down: nginx',
            'severity'  => 'critical',
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', $this->update('/alerts', '999'))
            ->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Container down: nginx');
        });
    }

    public function test_unknown_chat_id_gets_link_instructions(): void
    {
        $this->postJson('/api/telegram/webhook/test-secret', $this->update('/status', '42'))
            ->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], '42')
                && str_contains($request['text'], 'keinem ServerFlow-Account');
        });
    }

    public function test_unknown_command_returns_help_hint(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '555');

        $this->postJson('/api/telegram/webhook/test-secret', $this->update('/doesnotexist', '555'))
            ->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Unbekannter Befehl');
        });
    }

    public function test_help_command_lists_available_commands(): void
    {
        $user = User::factory()->create();
        $this->linkTelegram($user, '777');

        $this->postJson('/api/telegram/webhook/test-secret', $this->update('/help', '777'))
            ->assertOk();

        Http::assertSent(function ($request) {
            $text = $request['text'] ?? '';
            return str_contains($request->url(), 'sendMessage')
                && str_contains($text, '/status')
                && str_contains($text, '/servers')
                && str_contains($text, '/alerts');
        });
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

    private function update(string $text, string $chatId): array
    {
        return [
            'update_id' => 1,
            'message'   => [
                'message_id' => 1,
                'chat'       => ['id' => (int) $chatId, 'type' => 'private'],
                'text'       => $text,
            ],
        ];
    }
}
