<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeServerForUser(User $user): Server
    {
        $workspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);

        return Server::factory()->create([
            'user_id'      => $user->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    public function test_services_index_renders_for_owner(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);
        $server->services()->create(['name' => 'nginx', 'type' => 'web', 'status' => 'running']);

        $this->actingAs($user)
            ->get("/servers/{$server->id}/services")
            ->assertOk()
            ->assertSee('nginx');
    }

    public function test_services_create_form_renders_for_owner(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $this->actingAs($user)
            ->get("/servers/{$server->id}/services/create")
            ->assertOk()
            ->assertSee('Service hinzufügen');
    }

    public function test_owner_can_store_a_service(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $this->actingAs($user)
            ->post("/servers/{$server->id}/services", [
                'name'           => 'redis',
                'type'           => 'cache',
                'port'           => 6379,
                'check_interval' => 60,
            ])
            ->assertRedirect(route('servers.show', $server));

        $this->assertDatabaseHas('services', [
            'server_id' => $server->id,
            'name'      => 'redis',
            'type'      => 'cache',
            'port'      => 6379,
        ]);
    }

    public function test_services_index_forbidden_for_other_user(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = $this->makeServerForUser($other);

        $this->actingAs($user)
            ->get("/servers/{$server->id}/services")
            ->assertForbidden();
    }
}
