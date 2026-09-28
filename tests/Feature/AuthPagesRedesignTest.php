<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPagesRedesignTest extends TestCase
{
    private function src(string $file): string
    {
        return file_get_contents(resource_path('views/' . $file));
    }

    public function test_login_page_has_the_fields_and_submits_to_login(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();
        foreach (['name="email"', 'name="password"', 'name="remember"', 'action="' . route('login') . '"', route('register')] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_register_page_has_the_signup_fields(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();
        foreach ([
            'name="name"', 'name="email"', 'name="password"', 'name="password_confirmation"',
            'action="' . route('register') . '"', route('login'),
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    /** A structural check: both pages must still have a place to show validation errors for each field. */
    public function test_login_and_register_render_field_errors(): void
    {
        $loginSrc = $this->src('auth/login.blade.php');
        $this->assertStringContainsString("@error('email')", $loginSrc);
        $this->assertStringContainsString("@error('password')", $loginSrc);
        $this->assertStringContainsString('class="field-error"', $loginSrc);

        $registerSrc = $this->src('auth/register.blade.php');
        foreach (['name', 'email', 'password'] as $field) {
            $this->assertStringContainsString("@error('{$field}')", $registerSrc, $field);
        }
    }

    public function test_no_inline_styles_or_onclick_handlers(): void
    {
        foreach (['auth/login.blade.php', 'auth/register.blade.php'] as $file) {
            $src = $this->src($file);
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style|\son(click|submit)\s*=/i', $src, $file);
        }
    }

    public function test_auth_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['9. AUTH & SPECTATOR', 'END AUTH & SPECTATOR', '.auth-page', '.auth-card'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
        $this->assertStringNotContainsString('.account-type-toggle', $css);
    }
}
