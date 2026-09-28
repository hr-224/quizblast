<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeadAuthCodeRemovedTest extends TestCase
{
    public function test_player_login_and_register_views_are_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/auth/player-login.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/auth/player-register.blade.php'));
    }

    public function test_player_auth_controller_no_longer_has_the_dead_view_methods(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/Auth/PlayerAuthController.php'));
        $this->assertStringNotContainsString('function showLogin', $src);
        $this->assertStringNotContainsString('function showRegister', $src);
        // the routes/reachable behaviour this controller still owns must be untouched
        $this->assertStringContainsString('function register(', $src);
        $this->assertStringContainsString('function login(', $src);
        $this->assertStringContainsString('function logout(', $src);
        $this->assertStringContainsString('function stats(', $src);
    }

    /** The GET routes named player.login/player.register still redirect to the unified pages. */
    public function test_account_login_and_register_get_routes_still_redirect(): void
    {
        $this->get('/account/login')->assertRedirect(route('login'));
        $this->get('/account/register')->assertRedirect(route('register'));
    }
}
