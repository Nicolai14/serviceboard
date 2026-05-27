<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/servers')->assertRedirect('/login');
    }

    public function test_server_index_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/servers')->assertStatus(200);
    }

    public function test_server_index_only_shows_own_servers(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Server::factory()->create(['user_id' => $user->id,  'name' => 'My Server']);
        Server::factory()->create(['user_id' => $other->id, 'name' => 'Other Server']);

        $this->actingAs($user)
            ->get('/servers')
            ->assertSee('My Server')
            ->assertDontSee('Other Server');
    }

    public function test_user_can_create_a_server(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/servers', [
            'name'            => 'New Server',
            'hostname'        => 'server.example.com',
            'ip_address'      => '192.168.1.1',
            'ssh_port'        => 22,
            'ssh_user'        => 'root',
            'ssh_auth_method' => 'password',
            'ssh_password'    => 'secret',
        ])->assertRedirect();

        $this->assertDatabaseHas('servers', [
            'user_id'  => $user->id,
            'name'     => 'New Server',
            'hostname' => 'server.example.com',
        ]);
    }

    public function test_user_can_view_own_server(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get("/servers/{$server->id}")->assertStatus(200);
    }

    public function test_server_show_renders_usage_chart_with_history(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        foreach ([2, 1] as $hoursAgo) {
            $server->metrics()->create([
                'cpu_usage'      => 40,
                'memory_usage'   => 500,
                'memory_total'   => 1000,
                'disk_usage'     => 30,
                'disk_total'     => 100,
                'load_average'   => 0.5,
                'uptime_seconds' => 1000,
                'recorded_at'    => now()->subHours($hoursAgo),
            ]);
        }

        $this->actingAs($user)
            ->get("/servers/{$server->id}")
            ->assertOk()
            ->assertSee('Auslastung (24 h)');
    }

    public function test_user_cannot_view_another_users_server(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $server  = Server::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->get("/servers/{$server->id}")->assertStatus(403);
    }

    public function test_user_can_delete_own_server(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/servers/{$server->id}")
            ->assertRedirect('/servers');

        $this->assertSoftDeleted('servers', ['id' => $server->id]);
    }
}
