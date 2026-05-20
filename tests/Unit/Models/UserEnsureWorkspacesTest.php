<?php

namespace Tests\Unit\Models;

use App\Enums\WorkspaceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEnsureWorkspacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_exactly_one_personal_workspace(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();

        $this->assertSame(
            1,
            $user->workspaces()->where('type', WorkspaceType::Personal)->count(),
        );
    }

    public function test_creates_exactly_one_business_workspace(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();

        $this->assertSame(
            1,
            $user->workspaces()->where('type', WorkspaceType::Business)->count(),
        );
    }

    public function test_is_idempotent_for_personal_workspace(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();
        $user->ensureWorkspacesExist();

        $this->assertSame(
            1,
            $user->workspaces()->where('type', WorkspaceType::Personal)->count(),
        );
    }

    public function test_is_idempotent_for_business_workspace(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();
        $user->ensureWorkspacesExist();

        $this->assertSame(
            1,
            $user->workspaces()->where('type', WorkspaceType::Business)->count(),
        );
    }

    public function test_personal_workspace_has_correct_name_and_color(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();

        $personal = $user->workspaces()->where('type', WorkspaceType::Personal)->first();

        $this->assertSame('Privat', $personal->name);
        $this->assertSame('#3b82f6', $personal->color);
    }

    public function test_business_workspace_has_correct_name_and_color(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();

        $business = $user->workspaces()->where('type', WorkspaceType::Business)->first();

        $this->assertSame('Geschäftlich', $business->name);
        $this->assertSame('#f59e0b', $business->color);
    }

    public function test_personal_workspace_belongs_to_the_user(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();

        $personal = $user->workspaces()->where('type', WorkspaceType::Personal)->first();

        $this->assertSame($user->id, $personal->user_id);
    }

    public function test_business_workspace_belongs_to_the_user(): void
    {
        $user = User::factory()->create();

        $user->ensureWorkspacesExist();

        $business = $user->workspaces()->where('type', WorkspaceType::Business)->first();

        $this->assertSame($user->id, $business->user_id);
    }

    public function test_does_not_create_personal_when_it_already_exists(): void
    {
        $user = User::factory()->create();

        // Pre-create personal workspace via factory
        $user->workspaces()->create([
            'name'  => 'Privat',
            'type'  => WorkspaceType::Personal,
            'color' => '#3b82f6',
        ]);

        $user->ensureWorkspacesExist();

        $this->assertSame(1, $user->workspaces()->where('type', WorkspaceType::Personal)->count());
    }
}
