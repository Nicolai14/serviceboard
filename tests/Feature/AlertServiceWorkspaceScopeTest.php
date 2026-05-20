<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertServiceWorkspaceScopeTest extends TestCase
{
    use RefreshDatabase;

    private AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alertService = app(AlertService::class);
    }

    private function createWorkspaceWithServer(User $user, string $type = 'personal'): array
    {
        $workspace = Workspace::factory()->{$type}()->create(['user_id' => $user->id]);
        $server    = Server::factory()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        return [$workspace, $server];
    }

    // -------------------------------------------------------------------------
    // getUnreadCount

    public function test_get_unread_count_only_counts_alerts_for_servers_in_workspace(): void
    {
        $user = User::factory()->create();

        [$workspaceA, $serverA] = $this->createWorkspaceWithServer($user, 'personal');
        [$workspaceB, $serverB] = $this->createWorkspaceWithServer($user, 'business');

        Alert::factory()->count(3)->create(['user_id' => $user->id, 'server_id' => $serverA->id, 'is_read' => false]);
        Alert::factory()->count(5)->create(['user_id' => $user->id, 'server_id' => $serverB->id, 'is_read' => false]);

        $countA = $this->alertService->getUnreadCount($user, $workspaceA);
        $countB = $this->alertService->getUnreadCount($user, $workspaceB);

        $this->assertSame(3, $countA);
        $this->assertSame(5, $countB);
    }

    public function test_get_unread_count_without_workspace_counts_all_unread_alerts(): void
    {
        $user = User::factory()->create();

        [$workspaceA, $serverA] = $this->createWorkspaceWithServer($user, 'personal');
        [$workspaceB, $serverB] = $this->createWorkspaceWithServer($user, 'business');

        Alert::factory()->count(2)->create(['user_id' => $user->id, 'server_id' => $serverA->id, 'is_read' => false]);
        Alert::factory()->count(4)->create(['user_id' => $user->id, 'server_id' => $serverB->id, 'is_read' => false]);

        $this->assertSame(6, $this->alertService->getUnreadCount($user));
    }

    public function test_get_unread_count_excludes_already_read_alerts(): void
    {
        $user = User::factory()->create();

        [$workspace, $server] = $this->createWorkspaceWithServer($user, 'personal');

        Alert::factory()->count(2)->create(['user_id' => $user->id, 'server_id' => $server->id, 'is_read' => false]);
        Alert::factory()->count(3)->create(['user_id' => $user->id, 'server_id' => $server->id, 'is_read' => true]);

        $this->assertSame(2, $this->alertService->getUnreadCount($user, $workspace));
    }

    // -------------------------------------------------------------------------
    // markAllAsRead

    public function test_mark_all_as_read_only_marks_alerts_in_given_workspace(): void
    {
        $user = User::factory()->create();

        [$workspaceA, $serverA] = $this->createWorkspaceWithServer($user, 'personal');
        [$workspaceB, $serverB] = $this->createWorkspaceWithServer($user, 'business');

        $alertsA = Alert::factory()->count(3)->create(['user_id' => $user->id, 'server_id' => $serverA->id, 'is_read' => false]);
        $alertsB = Alert::factory()->count(2)->create(['user_id' => $user->id, 'server_id' => $serverB->id, 'is_read' => false]);

        $marked = $this->alertService->markAllAsRead($user, $workspaceA);

        $this->assertSame(3, $marked);

        // Workspace A alerts are now read
        foreach ($alertsA as $alert) {
            $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'is_read' => true]);
        }

        // Workspace B alerts remain unread
        foreach ($alertsB as $alert) {
            $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'is_read' => false]);
        }
    }

    public function test_mark_all_as_read_without_workspace_marks_all_user_alerts(): void
    {
        $user = User::factory()->create();

        [$workspaceA, $serverA] = $this->createWorkspaceWithServer($user, 'personal');
        [$workspaceB, $serverB] = $this->createWorkspaceWithServer($user, 'business');

        Alert::factory()->count(2)->create(['user_id' => $user->id, 'server_id' => $serverA->id, 'is_read' => false]);
        Alert::factory()->count(3)->create(['user_id' => $user->id, 'server_id' => $serverB->id, 'is_read' => false]);

        $marked = $this->alertService->markAllAsRead($user);

        $this->assertSame(5, $marked);
        $this->assertSame(0, $this->alertService->getUnreadCount($user));
    }

    public function test_mark_all_as_read_does_not_affect_other_users_alerts(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        [$workspaceUser,  $serverUser]  = $this->createWorkspaceWithServer($user,  'personal');
        [$workspaceOther, $serverOther] = $this->createWorkspaceWithServer($other, 'personal');

        Alert::factory()->create(['user_id' => $user->id,  'server_id' => $serverUser->id,  'is_read' => false]);
        $otherAlert = Alert::factory()->create(['user_id' => $other->id, 'server_id' => $serverOther->id, 'is_read' => false]);

        $this->alertService->markAllAsRead($user, $workspaceUser);

        $this->assertDatabaseHas('alerts', ['id' => $otherAlert->id, 'is_read' => false]);
    }

    // -------------------------------------------------------------------------
    // getForUser

    public function test_get_for_user_with_workspace_filter_excludes_other_workspace_alerts(): void
    {
        $user = User::factory()->create();

        [$workspaceA, $serverA] = $this->createWorkspaceWithServer($user, 'personal');
        [$workspaceB, $serverB] = $this->createWorkspaceWithServer($user, 'business');

        $alertA = Alert::factory()->create(['user_id' => $user->id, 'server_id' => $serverA->id]);
        $alertB = Alert::factory()->create(['user_id' => $user->id, 'server_id' => $serverB->id]);

        $results = $this->alertService->getForUser($user, [], $workspaceA);
        $ids     = $results->pluck('id');

        $this->assertTrue($ids->contains($alertA->id));
        $this->assertFalse($ids->contains($alertB->id));
    }

    public function test_get_for_user_without_workspace_returns_all_user_alerts(): void
    {
        $user = User::factory()->create();

        [$workspaceA, $serverA] = $this->createWorkspaceWithServer($user, 'personal');
        [$workspaceB, $serverB] = $this->createWorkspaceWithServer($user, 'business');

        Alert::factory()->count(2)->create(['user_id' => $user->id, 'server_id' => $serverA->id]);
        Alert::factory()->count(3)->create(['user_id' => $user->id, 'server_id' => $serverB->id]);

        $results = $this->alertService->getForUser($user);

        $this->assertSame(5, $results->total());
    }

    public function test_get_for_user_never_returns_other_users_alerts(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        [$workspaceUser,  $serverUser]  = $this->createWorkspaceWithServer($user,  'personal');
        [$workspaceOther, $serverOther] = $this->createWorkspaceWithServer($other, 'personal');

        Alert::factory()->create(['user_id' => $user->id,  'server_id' => $serverUser->id]);
        Alert::factory()->create(['user_id' => $other->id, 'server_id' => $serverOther->id]);

        $results = $this->alertService->getForUser($user);

        $this->assertSame(1, $results->total());
    }
}
