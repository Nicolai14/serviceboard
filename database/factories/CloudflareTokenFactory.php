<?php

namespace Database\Factories;

use App\Models\CloudflareToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CloudflareTokenFactory extends Factory
{
    protected $model = CloudflareToken::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'name'             => fake()->words(2, true),
            'api_token'        => fake()->regexify('[a-zA-Z0-9_\-]{40}'),
            'account_id'       => fake()->uuid(),
            'account_name'     => fake()->company(),
            'status'           => 'active',
            'error_message'    => null,
            'last_verified_at' => now(),
        ];
    }

    public function error(): static
    {
        return $this->state([
            'status'        => 'error',
            'error_message' => 'Token invalid',
        ]);
    }
}
