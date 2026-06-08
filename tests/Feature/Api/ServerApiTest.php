<?php

namespace Tests\Feature\Api;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_list_requires_auth(): void
    {
        $this->getJson('/api/v1/servers')->assertStatus(401);
    }

    public function test_user_can_list_own_servers(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Server::factory()->count(3)->create(['user_id' => $user->id]);
        Server::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/servers')
            ->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_server_list_includes_latest_metric(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        Metric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 12.3,
            'recorded_at' => now()->subMinutes(5),
        ]);
        Metric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 45.6,
            'recorded_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/servers')
            ->assertStatus(200)
            ->assertJsonPath('data.0.latest_metric.cpu_usage', 45.6);
    }

    public function test_server_show_returns_correct_structure(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'hostname', 'status', 'ip_address']]);
    }

    public function test_user_cannot_view_another_users_server(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}")
            ->assertStatus(403);
    }

    public function test_server_metrics_endpoint_returns_data(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        Metric::factory()->count(5)->create(['server_id' => $server->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}/metrics")
            ->assertStatus(200);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_metrics_limit_param_is_respected(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        Metric::factory()->count(10)->create(['server_id' => $server->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}/metrics?limit=3")
            ->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }
}
