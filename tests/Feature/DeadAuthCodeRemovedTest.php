<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeadAuthCodeRemovedTest extends TestCase
{
    public function test_player_auth_controller_is_gone(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Auth/PlayerAuthController.php'));
    }

    public function test_player_login_and_register_views_are_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/auth/player-login.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/auth/player-register.blade.php'));
    }

    /** The GET routes named player.login/player.register still redirect to the unified pages. */
    public function test_account_login_and_register_get_routes_still_redirect(): void
    {
        $this->get('/account/login')->assertRedirect(route('login'));
        $this->get('/account/register')->assertRedirect(route('register'));
    }

    /**
     * The POST routes that used to hit PlayerAuthController are gone entirely.
     * /account/register and /account/login still have a GET redirect route at the same
     * path, so Laravel reports a POST there as 405 Method Not Allowed rather than 404 —
     * /account/logout has no route left at all, so that one is a genuine 404.
     */
    public function test_account_post_routes_are_gone(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->post('/account/register', [])->assertMethodNotAllowed();
        $this->post('/account/login', [])->assertMethodNotAllowed();
        $this->post('/account/logout', [])->assertNotFound();
    }
}
