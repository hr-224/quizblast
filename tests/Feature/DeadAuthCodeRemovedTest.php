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
     * PlayerAuthController itself is gone (see test_player_auth_controller_is_gone above),
     * but a stale cached form still POSTing to /account/register or /account/login must
     * keep working — aliased onto the unified RegisterController/LoginController rather
     * than left to 404/405.
     */
    public function test_account_post_routes_are_aliased_onto_the_unified_controllers(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->post('/account/register', [
            'name' => 'Dead Code Test', 'email' => 'dead-code-register@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'dead-code-register@test.com']);

        \App\Models\User::create(['name' => 'Login Test', 'email' => 'dead-code-login@test.com', 'password' => 'password123']);
        $this->post('/account/login', ['email' => 'dead-code-login@test.com', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));

        $this->post('/account/logout')->assertRedirect(route('play.join'));
        $this->assertGuest();
    }
}
