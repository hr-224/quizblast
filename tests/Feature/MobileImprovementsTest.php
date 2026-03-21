<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileImprovementsTest extends TestCase
{
    /** Navbar layout contains hamburger button and dropdown */
    public function test_navbar_has_hamburger_and_dropdown(): void
    {
        $response = $this->get(route('play.join'));

        $response->assertStatus(200);
        $response->assertSee('id="nav-toggle"', false);
        $response->assertSee('id="nav-dropdown"', false);
        $response->assertSee('class="hamburger-btn"', false);
        $response->assertSee('id="nav-links"', false);
    }

    /** Navbar toggle script is present */
    public function test_navbar_toggle_script_is_present(): void
    {
        $response = $this->get(route('play.join'));

        $response->assertStatus(200);
        $response->assertSee('nav-toggle', false);
        $response->assertSee('dropdown.hidden', false);
    }
}
