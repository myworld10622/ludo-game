<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load(): void
    {
        $this->get('/')
            ->assertStatus(200);

        $this->get('/login')
            ->assertStatus(200);

        $this->get('/terms')->assertStatus(200);
        $this->get('/privacy')->assertStatus(200);
        $this->get('/fair-play')->assertStatus(200);
        $this->get('/responsible-gaming')->assertStatus(200);
        $this->get('/ludo')->assertStatus(200);
    }

    public function test_homepage_cards_api_returns_json(): void
    {
        $this->getJson('/api/homepage-cards')
            ->assertStatus(200)
            ->assertJsonIsArray();
    }

    public function test_admin_routes_redirect_to_login_when_unauthenticated(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');

        $this->get('/admin/settings/app')
            ->assertRedirect('/admin/login');
    }
}
