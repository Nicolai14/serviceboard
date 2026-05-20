<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_index_requires_auth(): void
    {
        $this->get('/alerts')->assertRedirect('/login');
    }

    public function test_alerts_index_is_accessible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/alerts')->assertStatus(200);
    }

    public function test_user_can_mark_alert_as_read(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $alert  = Alert::factory()->create(['user_id' => $user->id, 'server_id' => $server->id, 'is_read' => false]);

        $this->actingAs($user)
            ->post("/alerts/{$alert->id}/read")
            ->assertRedirect();

        $this->assertDatabaseHas('alerts', ['id' => $alert->id, 'is_read' => true]);
    }

    public function test_user_cannot_mark_another_users_alert_as_read(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $other->id]);
        $alert  = Alert::factory()->create(['user_id' => $other->id, 'server_id' => $server->id]);

        $this->actingAs($user)
            ->post("/alerts/{$alert->id}/read")
            ->assertStatus(403);
    }

    public function test_user_can_resolve_alert(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $alert  = Alert::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);

        $this->actingAs($user)
            ->post("/alerts/{$alert->id}/resolve")
            ->assertRedirect();

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_user_can_mark_all_alerts_as_read(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        Alert::factory()->count(3)->create(['user_id' => $user->id, 'server_id' => $server->id, 'is_read' => false]);

        $this->actingAs($user)
            ->post('/alerts/read-all')
            ->assertRedirect();

        $this->assertDatabaseMissing('alerts', ['user_id' => $user->id, 'is_read' => false]);
    }
}
