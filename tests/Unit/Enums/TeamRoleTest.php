<?php

namespace Tests\Unit\Enums;

use App\Enums\TeamRole;
use PHPUnit\Framework\TestCase;

class TeamRoleTest extends TestCase
{
    public function test_owner_can_manage_members(): void
    {
        $this->assertTrue(TeamRole::Owner->canManageMembers());
    }

    public function test_admin_can_manage_members(): void
    {
        $this->assertTrue(TeamRole::Admin->canManageMembers());
    }

    public function test_member_cannot_manage_members(): void
    {
        $this->assertFalse(TeamRole::Member->canManageMembers());
    }

    public function test_viewer_cannot_manage_members(): void
    {
        $this->assertFalse(TeamRole::Viewer->canManageMembers());
    }

    public function test_owner_admin_member_can_manage_servers(): void
    {
        $this->assertTrue(TeamRole::Owner->canManageServers());
        $this->assertTrue(TeamRole::Admin->canManageServers());
        $this->assertTrue(TeamRole::Member->canManageServers());
    }

    public function test_viewer_cannot_manage_servers(): void
    {
        $this->assertFalse(TeamRole::Viewer->canManageServers());
    }

    public function test_all_roles_can_view(): void
    {
        foreach (TeamRole::cases() as $role) {
            $this->assertTrue($role->canView(), "{$role->value} should be able to view");
        }
    }

    public function test_viewer_cannot_deploy(): void
    {
        $this->assertFalse(TeamRole::Viewer->canDeploy());
    }

    public function test_from_string_values(): void
    {
        $this->assertSame(TeamRole::Owner,  TeamRole::from('owner'));
        $this->assertSame(TeamRole::Admin,  TeamRole::from('admin'));
        $this->assertSame(TeamRole::Member, TeamRole::from('member'));
        $this->assertSame(TeamRole::Viewer, TeamRole::from('viewer'));
    }

    public function test_labels(): void
    {
        $this->assertSame('Owner',  TeamRole::Owner->label());
        $this->assertSame('Viewer', TeamRole::Viewer->label());
    }
}
