<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'server_id'   => Server::factory(),
            'type'        => fake()->randomElement(['server_offline', 'high_cpu', 'high_memory', 'disk_full']),
            'severity'    => fake()->randomElement(['info', 'warning', 'critical']),
            'message'     => fake()->sentence(),
            'context'     => [],
            'is_read'     => false,
            'resolved_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true]);
    }

    public function resolved(): static
    {
        return $this->state(['resolved_at' => now()]);
    }

    public function critical(): static
    {
        return $this->state(['severity' => 'critical']);
    }
}
