<?php

namespace App\Jobs;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunDeploymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600;

    public function __construct(public readonly int $deploymentId) {}

    public function handle(DeploymentService $deployments): void
    {
        $deployment = Deployment::find($this->deploymentId);

        if (!$deployment || !$deployment->isActive()) {
            return;
        }

        $deployments->run($deployment);
    }

    public function failed(Throwable $e): void
    {
        $deployment = Deployment::find($this->deploymentId);

        $deployment?->update([
            'status'      => DeploymentStatus::Failed->value,
            'finished_at' => now(),
            'log'         => ($deployment->log ?? '') . "\nJob failed: " . $e->getMessage(),
        ]);
    }
}
