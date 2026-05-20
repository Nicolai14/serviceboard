<?php

namespace Database\Factories;

use App\Models\CloudflareToken;
use App\Models\CloudflareZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CloudflareZoneFactory extends Factory
{
    protected $model = CloudflareZone::class;

    public function definition(): array
    {
        return [
            'cloudflare_token_id'   => CloudflareToken::factory(),
            'user_id'               => User::factory(),
            'zone_id'               => fake()->uuid(),
            'name'                  => fake()->domainName(),
            'status'                => 'active',
            'paused'                => false,
            'plan_name'             => 'Free',
            'type'                  => 'full',
            'name_servers'          => ['ns1.cloudflare.com', 'ns2.cloudflare.com'],
            'original_name_servers' => [],
            'synced_at'             => now(),
        ];
    }

    public function paused(): static
    {
        return $this->state(['paused' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
