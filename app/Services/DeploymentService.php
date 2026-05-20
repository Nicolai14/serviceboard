<?php

namespace App\Services;

use App\Contracts\DeploymentDriverContract;
use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\Server;
use App\Models\User;

class DeploymentService
{
    /** @var DeploymentDriverContract[] */
    private array $drivers = [];

    public function registerDriver(DeploymentDriverContract $driver): void
    {
        $this->drivers[] = $driver;
    }

    public function create(Server $server, User $user, array $data): Deployment
    {
        return Deployment::create([
            'server_id' => $server->id,
            'user_id'   => $user->id,
            'name'      => $data['name'],
            'type'      => $data['type'],
            'status'    => DeploymentStatus::Pending->value,
            'trigger'   => $data['trigger'] ?? 'manual',
            'config'    => $data['config'] ?? null,
        ]);
    }

    public function run(Deployment $deployment): bool
    {
        $driver = $this->resolveDriver($deployment);

        if (! $driver) {
            $deployment->update([
                'status' => DeploymentStatus::Failed->value,
                'log'    => 'No driver registered for type: ' . $deployment->type,
            ]);

            return false;
        }

        $deployment->update([
            'status'     => DeploymentStatus::Running->value,
            'started_at' => now(),
        ]);

        try {
            $result = $driver->run($deployment);

            $deployment->update([
                'status'      => $result ? DeploymentStatus::Success->value : DeploymentStatus::Failed->value,
                'finished_at' => now(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $deployment->update([
                'status'      => DeploymentStatus::Failed->value,
                'finished_at' => now(),
                'log'         => ($deployment->log ?? '') . "\nException: " . $e->getMessage(),
            ]);

            return false;
        }
    }

    private function resolveDriver(Deployment $deployment): ?DeploymentDriverContract
    {
        foreach ($this->drivers as $driver) {
            if ($driver->supports($deployment->type)) {
                return $driver;
            }
        }

        return null;
    }
}
