<?php

namespace Tests\Feature;

use App\Enums\WorkspaceType;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_workspace_stores_id_in_session_and_redirects_to_dashboard(): void
    {
        $user      = User::factory()->create();
        $workspace = Workspace::factory()->business()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/workspace/{$workspace->id}/switch")
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('active_workspace_id', $workspace->id);
    }

    public function test_cannot_switch_to_another_users_workspace(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $foreignWorkspace = Workspace::factory()->personal()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->post("/workspace/{$foreignWorkspace->id}/switch")
            ->assertForbidden();
    }

    public function test_server_index_only_shows_servers_from_active_workspace(): void
    {
        $user = User::factory()->create();

        $personalWorkspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $businessWorkspace = Workspace::factory()->business()->create(['user_id' => $user->id]);

        Server::factory()->create([
            'user_id'      => $user->id,
            'workspace_id' => $personalWorkspace->id,
            'name'         => 'Personal Server',
        ]);

        Server::factory()->create([
            'user_id'      => $user->id,
            'workspace_id' => $businessWorkspace->id,
            'name'         => 'Business Server',
        ]);

        // Switch to personal workspace, then visit server index
        $this->actingAs($user)
            ->withSession(['active_workspace_id' => $personalWorkspace->id])
            ->get('/servers')
            ->assertSee('Personal Server')
            ->assertDontSee('Business Server');
    }

    public function test_server_index_shows_active_workspace_server_after_switch(): void
    {
        $user = User::factory()->create();

        $personalWorkspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $businessWorkspace = Workspace::factory()->business()->create(['user_id' => $user->id]);

        Server::factory()->create([
            'user_id'      => $user->id,
            'workspace_id' => $personalWorkspace->id,
            'name'         => 'Personal Server',
        ]);

        Server::factory()->create([
            'user_id'      => $user->id,
            'workspace_id' => $businessWorkspace->id,
            'name'         => 'Business Server',
        ]);

        $this->actingAs($user)
            ->withSession(['active_workspace_id' => $businessWorkspace->id])
            ->get('/servers')
            ->assertSee('Business Server')
            ->assertDontSee('Personal Server');
    }

    public function test_set_active_workspace_middleware_migrates_orphaned_servers_to_personal_workspace(): void
    {
        $user = User::factory()->create();

        // Pre-create personal workspace so middleware has somewhere to migrate to
        $personalWorkspace = Workspace::factory()->personal()->create(['user_id' => $user->id]);

        // Server without a workspace_id — simulates pre-workspace legacy data
        $server = Server::factory()->create([
            'user_id'      => $user->id,
            'workspace_id' => null,
        ]);

        $this->actingAs($user)->get('/dashboard');

        $this->assertDatabaseHas('servers', [
            'id'           => $server->id,
            'workspace_id' => $personalWorkspace->id,
        ]);
    }

    public function test_switching_workspace_requires_authentication(): void
    {
        $workspace = Workspace::factory()->personal()->create();

        $this->post("/workspace/{$workspace->id}/switch")
            ->assertRedirect('/login');
    }
}
