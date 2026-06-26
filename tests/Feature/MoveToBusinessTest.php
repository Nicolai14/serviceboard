<?php

namespace Tests\Feature;

use App\Models\CloudflareZone;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveToBusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_moves_online_servers_and_domains_but_leaves_offline_servers(): void
    {
        $user     = User::factory()->create();
        $personal = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $business = Workspace::factory()->business()->create(['user_id' => $user->id]);

        $online  = Server::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id, 'status' => 'online']);
        $offline = Server::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id, 'status' => 'offline']);
        $zone    = CloudflareZone::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id]);

        $this->artisan('workspace:move-to-business', ['user' => $user->email])
            ->assertSuccessful();

        $this->assertSame($business->id, $online->fresh()->workspace_id);
        $this->assertSame($personal->id, $offline->fresh()->workspace_id, 'offline server stays in personal');
        $this->assertSame($business->id, $zone->fresh()->workspace_id);
    }

    public function test_include_offline_flag_moves_offline_servers_too(): void
    {
        $user     = User::factory()->create();
        $personal = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $business = Workspace::factory()->business()->create(['user_id' => $user->id]);

        $offline = Server::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id, 'status' => 'offline']);

        $this->artisan('workspace:move-to-business', ['user' => $user->email, '--include-offline' => true])
            ->assertSuccessful();

        $this->assertSame($business->id, $offline->fresh()->workspace_id);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $user     = User::factory()->create();
        $personal = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        Workspace::factory()->business()->create(['user_id' => $user->id]);

        $zone = CloudflareZone::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id]);

        $this->artisan('workspace:move-to-business', ['user' => $user->email, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame($personal->id, $zone->fresh()->workspace_id);
    }

    public function test_domains_index_only_shows_zones_of_active_workspace(): void
    {
        $user     = User::factory()->create();
        $personal = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $business = Workspace::factory()->business()->create(['user_id' => $user->id]);

        $personalZone = CloudflareZone::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id, 'name' => 'privat-domain.test']);
        $businessZone = CloudflareZone::factory()->create(['user_id' => $user->id, 'workspace_id' => $business->id, 'name' => 'firma-domain.test']);

        $this->actingAs($user)
            ->withSession(['active_workspace_id' => $business->id])
            ->get('/cloudflare')
            ->assertOk()
            ->assertSee('firma-domain.test')
            ->assertDontSee('privat-domain.test');
    }

    public function test_move_zone_endpoint_reassigns_workspace(): void
    {
        $user     = User::factory()->create();
        $personal = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $business = Workspace::factory()->business()->create(['user_id' => $user->id]);

        $zone = CloudflareZone::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id]);

        $this->actingAs($user)
            ->post(route('cloudflare.zones.move', $zone), ['workspace_id' => $business->id])
            ->assertRedirect();

        $this->assertSame($business->id, $zone->fresh()->workspace_id);
    }

    public function test_cannot_move_zone_into_foreign_workspace(): void
    {
        $user      = User::factory()->create();
        $personal  = Workspace::factory()->personal()->create(['user_id' => $user->id]);
        $foreignWs = Workspace::factory()->business()->create(['user_id' => User::factory()->create()->id]);

        $zone = CloudflareZone::factory()->create(['user_id' => $user->id, 'workspace_id' => $personal->id]);

        $this->actingAs($user)
            ->post(route('cloudflare.zones.move', $zone), ['workspace_id' => $foreignWs->id])
            ->assertForbidden();

        $this->assertSame($personal->id, $zone->fresh()->workspace_id);
    }
}
