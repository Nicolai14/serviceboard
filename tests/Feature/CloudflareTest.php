<?php

namespace Tests\Feature;

use App\Models\CloudflareToken;
use App\Models\CloudflareZone;
use App\Models\DnsRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudflareTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloudflare_index_requires_auth(): void
    {
        $this->get('/cloudflare')->assertRedirect('/login');
    }

    public function test_cloudflare_index_is_accessible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/cloudflare')->assertStatus(200);
    }

    public function test_dns_index_requires_auth(): void
    {
        $this->get('/cloudflare/dns')->assertRedirect('/login');
    }

    public function test_dns_index_is_accessible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/cloudflare/dns')->assertStatus(200);
    }

    public function test_zone_show_is_accessible_to_owner(): void
    {
        $user  = User::factory()->create();
        $token = CloudflareToken::factory()->create(['user_id' => $user->id]);
        $zone  = CloudflareZone::factory()->create(['user_id' => $user->id, 'cloudflare_token_id' => $token->id]);

        $this->actingAs($user)->get("/cloudflare/zones/{$zone->id}")->assertStatus(200);
    }

    public function test_zone_show_is_forbidden_to_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $token = CloudflareToken::factory()->create(['user_id' => $owner->id]);
        $zone  = CloudflareZone::factory()->create(['user_id' => $owner->id, 'cloudflare_token_id' => $token->id]);

        $this->actingAs($other)->get("/cloudflare/zones/{$zone->id}")->assertStatus(403);
    }

    public function test_dns_index_filters_by_zone(): void
    {
        $user  = User::factory()->create();
        $token = CloudflareToken::factory()->create(['user_id' => $user->id]);
        $zone1 = CloudflareZone::factory()->create(['user_id' => $user->id, 'cloudflare_token_id' => $token->id]);
        $zone2 = CloudflareZone::factory()->create(['user_id' => $user->id, 'cloudflare_token_id' => $token->id]);

        DnsRecord::factory()->create(['cloudflare_zone_id' => $zone1->id, 'name' => 'zone1.example.com', 'type' => 'A']);
        DnsRecord::factory()->create(['cloudflare_zone_id' => $zone2->id, 'name' => 'zone2.example.com', 'type' => 'A']);

        $this->actingAs($user)
            ->get("/cloudflare/dns?zone_id={$zone1->id}")
            ->assertSee('zone1.example.com')
            ->assertDontSee('zone2.example.com');
    }

    public function test_user_can_delete_own_token(): void
    {
        $user  = User::factory()->create();
        $token = CloudflareToken::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/cloudflare/tokens/{$token->id}")
            ->assertRedirect('/cloudflare');

        $this->assertDatabaseMissing('cloudflare_tokens', ['id' => $token->id]);
    }

    public function test_user_cannot_delete_another_users_token(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $token = CloudflareToken::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->delete("/cloudflare/tokens/{$token->id}")
            ->assertStatus(403);
    }
}
