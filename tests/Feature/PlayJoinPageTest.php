<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlayJoinPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_page_loads(): void
    {
        $response = $this->get(route('play.join'));
        $response->assertStatus(200);
    }

    public function test_join_page_has_canvas(): void
    {
        $response = $this->get(route('play.join'));
        $response->assertSee('<canvas', false);
    }

    public function test_join_page_has_pin_input(): void
    {
        $response = $this->get(route('play.join'));
        $response->assertSee('name="pin"', false);
    }

    public function test_join_page_has_nickname_input(): void
    {
        $response = $this->get(route('play.join'));
        $response->assertSee('name="nickname"', false);
    }

    public function test_join_page_shows_kicked_message(): void
    {
        $response = $this->get(route('play.join') . '?kicked=1');
        $response->assertSee('You were kicked from the game.');
    }

    public function test_join_page_has_host_account_link(): void
    {
        $response = $this->get(route('play.join'));
        $response->assertSee('Create a host account');
    }

    public function test_join_page_has_navbar_override(): void
    {
        $response = $this->get(route('play.join'));
        // dark glass navbar override is present
        $response->assertSee('rgba(15,10,30', false);
        // yellow underline is present
        $response->assertSee('#ffd000', false);
    }
}
