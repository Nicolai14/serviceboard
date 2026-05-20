<?php

namespace Tests\Feature\Api;

use App\Models\Alert;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_list_requires_auth(): void
    {
        $this->getJson('/api/v1/alerts')->assertStatus(401);
    }

    public function test_user_only_sees_own_alerts(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $otherServer = Server::factory()->create(['user_id' => $other->id]);

        Alert::factory()->count(3)->create(['user_id' => $user->id,  'server_id' => $server->id]);
        Alert::factory()->count(2)->create(['user_id' => $other->id, 'server_id' => $otherServer->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/alerts')
            ->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_alert_response_includes_expected_fields(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        Alert::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/alerts')
            ->assertJsonStructure(['data' => [['id', 'type', 'severity', 'message', 'is_read', 'created_at']]]);

    }

    public function test_user_can_mark_own_alert_as_read(): void
    {
        $user   = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $alert  = Alert::factory()->create(['user_id' => $user->id, 'server_id' => $server->id, 'is_read' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/alerts/{$alert->id}/read")
            ->assertStatus(200);

        $this->assertTrue((bool) $alert->fresh()->is_read);
    }

    public function test_user_cannot_mark_another_users_alert_as_read(): void
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $other->id]);
        $alert  = Alert::factory()->create(['user_id' => $other->id, 'server_id' => $server->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/alerts/{$alert->id}/read")
            ->assertStatus(403);
    }
}
