<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppHealthTest extends TestCase
{
    public function test_health_endpoint_returns_200(): void
    {
        $this->get('/up')->assertStatus(200);
    }

    public function test_login_page_returns_200(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_unauthenticated_root_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect();
    }

    public function test_dashboard_redirects_unauthenticated_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
