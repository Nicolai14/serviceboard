<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function personalWorkspace(User $user): Workspace
    {
        $user->ensureWorkspacesExist();

        return $user->workspaces()->where('type', 'personal')->firstOrFail();
    }

    public function test_index_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/workflow')
            ->assertOk()
            ->assertSee('Projekt Workflow');
    }

    public function test_index_redirects_guest_to_login(): void
    {
        $this->get('/workflow')->assertRedirect('/login');
    }

    public function test_save_persists_nodes_and_edges(): void
    {
        $user      = User::factory()->create();
        $workspace = $this->personalWorkspace($user);

        $response = $this->actingAs($user)->putJson('/workflow', [
            'nodes' => [
                ['id' => 'tmp-1', 'type' => 'app',    'label' => 'HorseFlow App', 'x' => 100, 'y' => 120, 'meta' => ['url' => 'https://horseflow.app']],
                ['id' => 'tmp-2', 'type' => 'docker', 'label' => 'Backend',       'x' => 400, 'y' => 120, 'meta' => []],
            ],
            'edges' => [
                ['from' => 'tmp-1', 'to' => 'tmp-2', 'label' => 'HTTPS'],
            ],
        ]);

        $response->assertOk()->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('workflow_nodes', [
            'workspace_id' => $workspace->id,
            'type'         => 'app',
            'label'        => 'HorseFlow App',
            'position_x'   => 100,
        ]);
        $this->assertDatabaseHas('workflow_nodes', [
            'workspace_id' => $workspace->id,
            'type'         => 'docker',
            'label'        => 'Backend',
        ]);

        $this->assertEquals(2, $workspace->workflowNodes()->count());
        $this->assertEquals(1, $workspace->workflowEdges()->count());

        $edge = $workspace->workflowEdges()->first();
        $this->assertSame('HTTPS', $edge->label);
        $this->assertSame('app', $edge->fromNode->type->value);
        $this->assertSame('docker', $edge->toNode->type->value);
    }

    public function test_save_replaces_the_previous_graph(): void
    {
        $user      = User::factory()->create();
        $workspace = $this->personalWorkspace($user);

        $this->actingAs($user)->putJson('/workflow', [
            'nodes' => [['id' => 'a', 'type' => 'server', 'label' => 'Old', 'x' => 0, 'y' => 0]],
            'edges' => [],
        ])->assertOk();

        $this->actingAs($user)->putJson('/workflow', [
            'nodes' => [['id' => 'b', 'type' => 'domain', 'label' => 'New', 'x' => 10, 'y' => 10]],
            'edges' => [],
        ])->assertOk();

        $this->assertEquals(1, $workspace->workflowNodes()->count());
        $this->assertDatabaseHas('workflow_nodes', ['workspace_id' => $workspace->id, 'label' => 'New']);
        $this->assertDatabaseMissing('workflow_nodes', ['workspace_id' => $workspace->id, 'label' => 'Old']);
    }

    public function test_save_drops_self_and_dangling_edges(): void
    {
        $user      = User::factory()->create();
        $workspace = $this->personalWorkspace($user);

        $this->actingAs($user)->putJson('/workflow', [
            'nodes' => [['id' => 'n1', 'type' => 'app', 'label' => 'Solo', 'x' => 0, 'y' => 0]],
            'edges' => [
                ['from' => 'n1', 'to' => 'n1'],        // self loop
                ['from' => 'n1', 'to' => 'ghost'],     // dangling endpoint
            ],
        ])->assertOk();

        $this->assertEquals(0, $workspace->workflowEdges()->count());
    }

    public function test_save_rejects_invalid_node_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/workflow', [
            'nodes' => [['id' => 'x', 'type' => 'spaceship', 'label' => 'Nope', 'x' => 0, 'y' => 0]],
            'edges' => [],
        ])->assertStatus(422);
    }
}
