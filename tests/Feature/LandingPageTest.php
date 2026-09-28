<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads_at_root(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('How it works');
    }

    public function test_landing_page_has_pin_join_form(): void
    {
        $response = $this->get(route('landing'));
        $response->assertSee('name="pin"', false);
        $response->assertSee(route('play.join'), false);
    }

    public function test_landing_page_links_to_host_signup(): void
    {
        $response = $this->get(route('landing'));
        $response->assertSee(route('register'), false);
    }

    public function test_landing_page_uses_the_shared_shell(): void
    {
        $response = $this->get(route('landing'));
        $response->assertSee('id="nav-toggle"', false);
        $response->assertSee('rel="preload" href="/fonts/lexend-latin.woff2"', false);
    }

    public function test_navbar_logo_points_home(): void
    {
        $response = $this->get(route('landing'));
        $response->assertSee('href="' . route('landing') . '" class="navbar-brand"', false);
    }

    public function test_play_join_page_still_works_standalone(): void
    {
        $response = $this->get(route('play.join'));
        $response->assertStatus(200);
        $response->assertSee('name="nickname"', false);
    }
}
