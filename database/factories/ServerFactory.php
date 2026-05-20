<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'name'            => fake()->words(2, true),
            'hostname'        => fake()->domainName(),
            'ip_address'      => fake()->ipv4(),
            'ssh_port'        => 22,
            'ssh_user'        => 'monitor',
            'ssh_auth_method' => 'password',
            'ssh_password'    => 'secret',
            'status'          => 'online',
            'os'              => 'Ubuntu 24.04',
            'tags'            => [],
            'poll_failures'   => 0,
        ];
    }

    public function online(): static
    {
        return $this->state(['status' => 'online']);
    }

    public function offline(): static
    {
        return $this->state(['status' => 'offline']);
    }

    public function unknown(): static
    {
        return $this->state(['status' => 'unknown']);
    }
}
