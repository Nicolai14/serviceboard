<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_to_guests(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_cannot_login_with_unknown_email(): void
    {
        $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        // Exhaust the allowed attempts with a wrong password.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // The next attempt — even with the correct password — is locked out.
        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Zu viele Anmeldeversuche',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
