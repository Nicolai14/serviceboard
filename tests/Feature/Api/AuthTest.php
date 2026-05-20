<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_api_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->postJson('/api/v1/auth/token', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'phpunit',
        ])
        ->assertStatus(200)
        ->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_token_creation_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/token', [
            'email'       => $user->email,
            'password'    => 'wrong',
            'device_name' => 'phpunit',
        ])->assertStatus(422);
    }

    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user')
            ->assertStatus(200)
            ->assertJsonFragment(['email' => $user->email]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/user')->assertStatus(401);
    }

    public function test_user_can_revoke_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/v1/auth/token')
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Token revoked.']);
    }
}
