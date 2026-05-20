<?php

namespace Database\Factories;

use App\Models\DockerContainer;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class DockerContainerFactory extends Factory
{
    protected $model = DockerContainer::class;

    public function definition(): array
    {
        return [
            'server_id'       => Server::factory(),
            'container_id'    => fake()->regexify('[a-f0-9]{12}'),
            'name'            => fake()->slug(2),
            'image'           => fake()->slug(1) . ':latest',
            'state'           => 'running',
            'status_text'     => 'Up 2 hours',
            'cpu_percent'     => fake()->randomFloat(2, 0, 100),
            'memory_usage_mb' => fake()->randomFloat(1, 10, 512),
            'memory_limit_mb' => 1024.0,
            'memory_percent'  => fake()->randomFloat(1, 0, 50),
            'ports'           => [],
            'synced_at'       => now(),
        ];
    }

    public function stopped(): static
    {
        return $this->state(['state' => 'exited', 'status_text' => 'Exited (0)']);
    }

    public function running(): static
    {
        return $this->state(['state' => 'running']);
    }
}
