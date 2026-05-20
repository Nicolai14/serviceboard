<?php

namespace Tests\Feature\Api;

use App\Models\DockerContainer;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContainerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_containers_requires_auth(): void
    {
        $this->getJson('/api/v1/containers')->assertStatus(401);
    }

    public function test_all_containers_only_returns_own_servers_containers(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $otherServer = Server::factory()->create(['user_id' => $other->id]);

        DockerContainer::factory()->count(3)->create(['server_id' => $server->id]);
        DockerContainer::factory()->count(2)->create(['server_id' => $otherServer->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/containers')
            ->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_per_server_containers_requires_ownership(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}/containers")
            ->assertStatus(403);
    }

    public function test_per_server_containers_returns_correct_data(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        DockerContainer::factory()->count(4)->create(['server_id' => $server->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}/containers")
            ->assertStatus(200);

        $this->assertCount(4, $response->json('data'));
    }

    public function test_container_response_includes_expected_fields(): void
    {
        $user      = User::factory()->create();
        $server    = Server::factory()->create(['user_id' => $user->id]);
        DockerContainer::factory()->create(['server_id' => $server->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/servers/{$server->id}/containers")
            ->assertJsonStructure(['data' => [['id', 'name', 'image', 'state', 'cpu_percent']]]);
    }
}
