<?php

namespace Tests\Feature;

use App\Jobs\RunDeploymentJob;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use App\Services\DeploymentService;
use App\Services\SSHService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeploymentTest extends TestCase
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

    public function test_index_and_create_render_for_owner(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $this->actingAs($user)->get("/servers/{$server->id}/deployments")->assertOk();
        $this->actingAs($user)
            ->get("/servers/{$server->id}/deployments/create")
            ->assertOk()
            ->assertSee('Neues Deployment');
    }

    public function test_store_creates_deployment_and_dispatches_job(): void
    {
        Queue::fake();

        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);

        $response = $this->actingAs($user)->post("/servers/{$server->id}/deployments", [
            'name'      => 'Release 1',
            'type'      => 'script',
            'directory' => '/root/app',
            'script'    => 'echo deploy',
        ]);

        $deployment = $server->deployments()->first();

        $this->assertNotNull($deployment);
        $response->assertRedirect(route('servers.deployments.show', [$server, $deployment]));
        $this->assertSame('pending', $deployment->status);
        $this->assertSame('echo deploy', $deployment->config['script']);

        Queue::assertPushed(RunDeploymentJob::class, fn ($job) => $job->deploymentId === $deployment->id);
    }

    public function test_show_renders_for_owner(): void
    {
        $user   = User::factory()->create();
        $server = $this->makeServerForUser($user);
        $deployment = $server->deployments()->create([
            'user_id' => $user->id,
            'name'    => 'Release 1',
            'type'    => 'script',
            'status'  => 'success',
            'trigger' => 'manual',
            'config'  => ['script' => 'echo hi'],
            'log'     => '$ echo hi',
        ]);

        $this->actingAs($user)
            ->get("/servers/{$server->id}/deployments/{$deployment->id}")
            ->assertOk()
            ->assertSee('Release 1');
    }

    public function test_index_forbidden_for_other_user(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = $this->makeServerForUser($other);

        $this->actingAs($user)->get("/servers/{$server->id}/deployments")->assertForbidden();
    }

    public function test_running_a_script_deployment_records_success(): void
    {
        $user       = User::factory()->create();
        $server     = $this->makeServerForUser($user);
        $deployment = $server->deployments()->create([
            'user_id' => $user->id,
            'name'    => 'Deploy',
            'type'    => 'script',
            'status'  => 'pending',
            'trigger' => 'manual',
            'config'  => ['script' => 'echo hi', 'directory' => '/root/app'],
        ]);

        $this->mock(SSHService::class, function ($mock) {
            $mock->shouldReceive('runScript')->once()->andReturn(['output' => 'hi', 'exit_code' => 0]);
        });

        $result = app(DeploymentService::class)->run($deployment);

        $this->assertTrue($result);
        $deployment->refresh();
        $this->assertSame('success', $deployment->status);
        $this->assertNotNull($deployment->finished_at);
        $this->assertStringContainsString('hi', $deployment->log);
    }

    public function test_failed_command_marks_deployment_failed(): void
    {
        $user       = User::factory()->create();
        $server     = $this->makeServerForUser($user);
        $deployment = $server->deployments()->create([
            'user_id' => $user->id,
            'name'    => 'Deploy',
            'type'    => 'script',
            'status'  => 'pending',
            'trigger' => 'manual',
            'config'  => ['script' => 'false'],
        ]);

        $this->mock(SSHService::class, function ($mock) {
            $mock->shouldReceive('runScript')->once()->andReturn(['output' => '', 'exit_code' => 1]);
        });

        $result = app(DeploymentService::class)->run($deployment);

        $this->assertFalse($result);
        $this->assertSame('failed', $deployment->fresh()->status);
    }
}
