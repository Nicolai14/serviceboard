<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ServiceHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceHealthTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(array $attributes = []): Service
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        return $server->services()->create(array_merge([
            'name'           => 'web',
            'type'           => 'web',
            'status'         => 'unknown',
            'check_interval' => 60,
        ], $attributes));
    }

    public function test_successful_http_check_marks_service_running(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $service = $this->makeService(['check_url' => 'https://example.com/health']);

        $status = app(ServiceHealthService::class)->check($service);

        $this->assertSame('running', $status);
        $service->refresh();
        $this->assertSame('running', $service->status);
        $this->assertNotNull($service->last_checked_at);
        $this->assertNotNull($service->last_latency_ms);
    }

    public function test_failing_http_check_marks_error_and_raises_alert(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $service = $this->makeService([
            'check_url' => 'https://example.com/health',
            'status'    => 'running',
        ]);

        app(ServiceHealthService::class)->check($service);

        $this->assertSame('error', $service->fresh()->status);
        $this->assertDatabaseHas('alerts', [
            'server_id' => $service->server_id,
            'type'      => 'service_down',
            'severity'  => 'warning',
        ]);
    }

    public function test_recovery_resolves_open_service_down_alert(): void
    {
        $service = $this->makeService([
            'check_url' => 'https://example.com/health',
            'status'    => 'error',
        ]);

        $alert = $service->server->alerts()->create([
            'user_id'  => $service->server->user_id,
            'type'     => 'service_down',
            'severity' => 'warning',
            'message'  => 'down',
            'context'  => ['service_id' => $service->id],
        ]);

        Http::fake(['*' => Http::response('ok', 200)]);

        app(ServiceHealthService::class)->check($service);

        $this->assertSame('running', $service->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_notification_dispatched_when_notify_on_down_enabled(): void
    {
        Http::fake(['*' => Http::response('boom', 503)]);

        $mock = $this->mock(NotificationService::class);
        $mock->shouldReceive('dispatch')->once();

        $service = $this->makeService([
            'check_url'      => 'https://example.com/health',
            'status'         => 'running',
            'notify_on_down' => true,
        ]);

        app(ServiceHealthService::class)->check($service);

        $this->assertSame('error', $service->fresh()->status);
    }

    public function test_check_is_due_respects_interval(): void
    {
        $service = $this->makeService(['check_interval' => 300]);

        $this->assertTrue($service->isCheckDue(), 'never-checked service is due');

        $service->update(['last_checked_at' => now()]);
        $this->assertFalse($service->fresh()->isCheckDue(), 'just-checked service is not due');

        $service->update(['last_checked_at' => now()->subSeconds(301)]);
        $this->assertTrue($service->fresh()->isCheckDue(), 'service past its interval is due');
    }
}
