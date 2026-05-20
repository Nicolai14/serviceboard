<?php

namespace Tests\Feature;

use App\Jobs\PollServerMetricsJob;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ServerService;
use App\Services\SSHService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServerSshActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeServerForUser(User $user): Server
    {
        $workspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);

        return Server::factory()->create([
            'user_id'         => $user->id,
            'workspace_id'    => $workspace->id,
            'ssh_auth_method' => 'password',
            'ssh_password'    => 'secret',
        ]);
    }

    // -------------------------------------------------------------------------
    // check-online

    public function test_check_online_returns_online_true_when_server_is_reachable(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $this->mock(ServerService::class, function ($mock) {
            $mock->shouldReceive('checkConnectivity')->once()->andReturn(true);
            $mock->shouldReceive('getAllForUser')->passthru();
            $mock->shouldReceive('testSSHConnection')->passthru();
        });

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/check-online")
            ->assertOk()
            ->assertJson(['online' => true]);
    }

    public function test_check_online_returns_online_false_when_server_is_unreachable(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $this->mock(ServerService::class, function ($mock) {
            $mock->shouldReceive('checkConnectivity')->once()->andReturn(false);
            $mock->shouldReceive('getAllForUser')->passthru();
        });

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/check-online")
            ->assertOk()
            ->assertJson(['online' => false]);
    }

    public function test_check_online_returns_403_for_server_belonging_to_another_user(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $server  = $this->makeServerForUser($other);

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/check-online")
            ->assertForbidden();
    }

    public function test_check_online_redirects_unauthenticated_users(): void
    {
        $server = Server::factory()->create();

        $this->post("/servers/{$server->id}/check-online")
            ->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // test-ssh

    public function test_test_ssh_returns_json_with_success_key(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $successPayload = [
            'success'    => true,
            'step'       => 'auth',
            'message'    => 'SSH-Verbindung erfolgreich.',
            'latency_ms' => 42,
        ];

        $this->mock(ServerService::class, function ($mock) use ($successPayload) {
            $mock->shouldReceive('testSSHConnection')->once()->andReturn($successPayload);
            $mock->shouldReceive('getAllForUser')->passthru();
        });

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/test-ssh")
            ->assertOk()
            ->assertJsonStructure(['success', 'step', 'message', 'latency_ms'])
            ->assertJson(['success' => true]);
    }

    public function test_test_ssh_returns_403_for_server_belonging_to_another_user(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = $this->makeServerForUser($other);

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/test-ssh")
            ->assertForbidden();
    }

    public function test_test_ssh_redirects_unauthenticated_users(): void
    {
        $server = Server::factory()->create();

        $this->post("/servers/{$server->id}/test-ssh")
            ->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // poll-now

    public function test_poll_now_dispatches_job_and_returns_dispatched_true(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/poll-now")
            ->assertOk()
            ->assertJson(['dispatched' => true]);

        Queue::assertPushedOn('monitoring', PollServerMetricsJob::class, function ($job) use ($server) {
            return $job->serverId === $server->id;
        });
    }

    public function test_poll_now_returns_422_when_server_has_no_ssh_credentials(): void
    {
        Queue::fake();

        $user      = User::factory()->create();
        $workspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);

        $server = Server::factory()->create([
            'user_id'         => $user->id,
            'workspace_id'    => $workspace->id,
            'ssh_auth_method' => 'password',
            'ssh_password'    => null,
        ]);

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/poll-now")
            ->assertUnprocessable()
            ->assertJson(['dispatched' => false]);

        Queue::assertNothingPushed();
    }

    public function test_poll_now_returns_403_for_server_belonging_to_another_user(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = $this->makeServerForUser($other);

        $this->actingAs($user)
            ->postJson("/servers/{$server->id}/poll-now")
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_poll_now_redirects_unauthenticated_users(): void
    {
        $server = Server::factory()->create();

        $this->post("/servers/{$server->id}/poll-now")
            ->assertRedirect('/login');
    }
}
