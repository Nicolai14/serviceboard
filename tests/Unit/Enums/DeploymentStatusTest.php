<?php

namespace Tests\Unit\Enums;

use App\Enums\DeploymentStatus;
use PHPUnit\Framework\TestCase;

class DeploymentStatusTest extends TestCase
{
    public function test_terminal_statuses(): void
    {
        $this->assertTrue(DeploymentStatus::Success->isTerminal());
        $this->assertTrue(DeploymentStatus::Failed->isTerminal());
        $this->assertTrue(DeploymentStatus::Cancelled->isTerminal());
    }

    public function test_active_statuses_are_not_terminal(): void
    {
        $this->assertFalse(DeploymentStatus::Pending->isTerminal());
        $this->assertFalse(DeploymentStatus::Running->isTerminal());
    }

    public function test_active_statuses(): void
    {
        $this->assertTrue(DeploymentStatus::Pending->isActive());
        $this->assertTrue(DeploymentStatus::Running->isActive());
    }

    public function test_terminal_statuses_are_not_active(): void
    {
        $this->assertFalse(DeploymentStatus::Success->isActive());
        $this->assertFalse(DeploymentStatus::Failed->isActive());
        $this->assertFalse(DeploymentStatus::Cancelled->isActive());
    }

    public function test_color_values(): void
    {
        $this->assertSame('green', DeploymentStatus::Success->color());
        $this->assertSame('red',   DeploymentStatus::Failed->color());
        $this->assertSame('blue',  DeploymentStatus::Running->color());
        $this->assertSame('yellow',DeploymentStatus::Pending->color());
        $this->assertSame('zinc',  DeploymentStatus::Cancelled->color());
    }

    public function test_from_string_values(): void
    {
        $this->assertSame(DeploymentStatus::Success,   DeploymentStatus::from('success'));
        $this->assertSame(DeploymentStatus::Failed,    DeploymentStatus::from('failed'));
        $this->assertSame(DeploymentStatus::Cancelled, DeploymentStatus::from('cancelled'));
    }
}
