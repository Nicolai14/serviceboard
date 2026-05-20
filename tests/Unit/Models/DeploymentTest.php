<?php

namespace Tests\Unit\Models;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeploymentTest extends TestCase
{
    public function test_duration_returns_null_without_timestamps(): void
    {
        $deployment = new Deployment();
        $this->assertNull($deployment->duration);
    }

    public function test_duration_calculates_seconds_between_start_and_finish(): void
    {
        $deployment = new Deployment([
            'started_at'  => Carbon::parse('2025-01-01 12:00:00'),
            'finished_at' => Carbon::parse('2025-01-01 12:01:45'),
        ]);

        $this->assertSame(105, $deployment->duration);
    }

    public function test_is_active_for_pending_status(): void
    {
        $deployment = new Deployment(['status' => DeploymentStatus::Pending->value]);
        $this->assertTrue($deployment->isActive());
    }

    public function test_is_active_for_running_status(): void
    {
        $deployment = new Deployment(['status' => DeploymentStatus::Running->value]);
        $this->assertTrue($deployment->isActive());
    }

    public function test_is_not_active_for_terminal_statuses(): void
    {
        foreach ([DeploymentStatus::Success, DeploymentStatus::Failed, DeploymentStatus::Cancelled] as $status) {
            $deployment = new Deployment(['status' => $status->value]);
            $this->assertFalse($deployment->isActive(), "{$status->value} should not be active");
        }
    }
}
