<?php

namespace Database\Factories;

use App\Models\CloudflareZone;
use App\Models\DnsRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class DnsRecordFactory extends Factory
{
    protected $model = DnsRecord::class;

    public function definition(): array
    {
        return [
            'cloudflare_zone_id' => CloudflareZone::factory(),
            'cf_record_id'       => fake()->uuid(),
            'type'               => fake()->randomElement(['A', 'AAAA', 'CNAME', 'MX', 'TXT']),
            'name'               => fake()->domainName(),
            'content'            => fake()->ipv4(),
            'proxied'            => false,
            'proxiable'          => true,
            'ttl'                => 1,
            'priority'           => null,
            'comment'            => null,
            'synced_at'          => now(),
        ];
    }

    public function proxied(): static
    {
        return $this->state(['proxied' => true]);
    }

    public function ofType(string $type): static
    {
        return $this->state(['type' => strtoupper($type)]);
    }

    public function withTtl(int $ttl): static
    {
        return $this->state(['ttl' => $ttl]);
    }
}
