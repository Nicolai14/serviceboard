<?php

namespace Database\Factories;

use App\Models\Metric;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetricFactory extends Factory
{
    protected $model = Metric::class;

    public function definition(): array
    {
        $memTotal  = 8192.0;
        $diskTotal = 102400.0;

        return [
            'server_id'      => Server::factory(),
            'cpu_usage'      => fake()->randomFloat(1, 0, 100),
            'memory_usage'   => fake()->randomFloat(1, 512, $memTotal),
            'memory_total'   => $memTotal,
            'disk_usage'     => fake()->randomFloat(1, 1000, $diskTotal),
            'disk_total'     => $diskTotal,
            'load_average'   => fake()->randomFloat(2, 0, 8),
            'uptime_seconds' => fake()->numberBetween(3600, 2592000),
            'recorded_at'    => now(),
        ];
    }
}
