<?php

namespace Tests\Feature;

use App\Jobs\PollServerMetricsJob;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AlertService;
use App\Services\ServerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AlertThresholdTest extends TestCase
{
    use RefreshDatabase;

    private function serverWithMetric(array $serverAttrs, float $cpu): Server
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(array_merge(['user_id' => $user->id], $serverAttrs));

        $server->metrics()->create([
            'cpu_usage'      => $cpu,
            'memory_usage'   => 0,
            'memory_total'   => 1000,
            'disk_usage'     => 0,
            'disk_total'     => 1000,
            'load_average'   => 0,
            'uptime_seconds' => 0,
            'recorded_at'    => now(),
        ]);

        return $server;
    }

    private function runPoll(Server $server): void
    {
        $serverService = Mockery::mock(ServerService::class);
        $serverService->shouldReceive('pollMetrics')->once();

        (new PollServerMetricsJob($server->id))->handle($serverService, app(AlertService::class));
    }

    public function test_thresholds_merge_defaults_with_overrides(): void
    {
        $server = Server::factory()->make(['alert_thresholds' => ['cpu_warning' => 50]]);

        $t = $server->thresholds();

        $this->assertSame(50, $t['cpu_warning']);                                  // override
        $this->assertSame(Server::DEFAULT_THRESHOLDS['cpu_critical'], $t['cpu_critical']); // default
    }

    public function test_custom_warning_threshold_triggers_warning(): void
    {
        $server = $this->serverWithMetric(
            ['alerts_enabled' => true, 'alert_thresholds' => ['cpu_warning' => 50, 'cpu_critical' => 95]],
            cpu: 60,
        );

        $this->runPoll($server);

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'type'      => 'high_cpu',
            'severity'  => 'warning',
        ]);
    }

    public function test_custom_critical_threshold_triggers_critical(): void
    {
        $server = $this->serverWithMetric(
            ['alerts_enabled' => true, 'alert_thresholds' => ['cpu_warning' => 50, 'cpu_critical' => 95]],
            cpu: 96,
        );

        $this->runPoll($server);

        $this->assertDatabaseHas('alerts', [
            'server_id' => $server->id,
            'type'      => 'high_cpu',
            'severity'  => 'critical',
        ]);
    }

    public function test_disabled_alerts_suppress_threshold_alerts(): void
    {
        $server = $this->serverWithMetric(
            ['alerts_enabled' => false, 'alert_thresholds' => ['cpu_warning' => 10]],
            cpu: 99,
        );

        $this->runPoll($server);

        $this->assertDatabaseMissing('alerts', ['server_id' => $server->id]);
    }

    public function test_edit_page_renders_threshold_settings(): void
    {
        $user      = User::factory()->create();
        $workspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $server    = Server::factory()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->actingAs($user)
            ->get("/servers/{$server->id}/edit")
            ->assertOk()
            ->assertSee('Alert-Schwellwerte');
    }

    public function test_owner_can_update_alert_settings(): void
    {
        $user      = User::factory()->create();
        $workspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $server    = Server::factory()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->actingAs($user)
            ->patch("/servers/{$server->id}/alert-settings", [
                'thresholds' => [
                    'cpu_warning'     => 60, 'cpu_critical' => 85,
                    'memory_warning'  => 70, 'memory_critical' => 88,
                    'disk_warning'    => 80, 'disk_critical' => 92,
                ],
            ])
            ->assertRedirect(route('servers.edit', $server));

        $server->refresh();
        $this->assertFalse($server->alerts_enabled);            // checkbox unchecked → false
        $this->assertSame(60, $server->thresholds()['cpu_warning']);
        $this->assertSame(92, $server->thresholds()['disk_critical']);
    }
}
