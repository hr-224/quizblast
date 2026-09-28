<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlayerJoinRedesignTest extends TestCase
{
    private function html(): string
    {
        return $this->get(route('play.join'))->assertOk()->getContent();
    }

    public function test_pin_and_nickname_fields_are_phone_friendly(): void
    {
        $html = $this->html();
        foreach ([
            'name="pin"', 'inputmode="numeric"', 'pattern="[0-9]*"', 'maxlength="6"', 'autocomplete="off"',
            'name="nickname"', 'maxlength="20"', 'for="join-pin"', 'for="join-nick"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_backdrop_canvas_is_party_only_with_a_static_calm_fallback(): void
    {
        $html = $this->html();
        $this->assertStringContainsString('<canvas id="join-bg" class="join-bg fx-party"', $html);
        $this->assertStringContainsString('class="join-static"', $html);
        $this->assertStringContainsString('/js/shapes.js', $html);
        $this->assertStringContainsString('/js/join-bg.js', $html);
    }

    public function test_keeps_kicked_notice_and_unified_signup_link(): void
    {
        $this->get(route('play.join') . '?kicked=1')->assertSee('You were kicked from the game.');
        $this->assertStringContainsString('Create an account', $this->html());
        // Player and host accounts share one signup form now — no separate "Player account" link.
        $this->assertStringNotContainsString('Player account', $this->html());
        $this->assertStringNotContainsString(route('player.register'), $this->html());
    }

    public function test_join_view_has_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/play/join.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }

    public function test_backdrop_script_is_es5_and_reacts_to_effect_changes(): void
    {
        $src = file_get_contents(public_path('js/join-bg.js'));
        foreach (['=>', 'const ', 'let ', '`'] as $modern) {
            $this->assertStringNotContainsString($modern, $src, "join-bg.js must stay ES5 ({$modern})");
        }
        foreach (['qb:fx', 'visibilitychange', 'QB.Shapes', 'requestAnimationFrame', 'cancelAnimationFrame'] as $needle) {
            $this->assertStringContainsString($needle, $src, $needle);
        }
    }

    public function test_join_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.join-static', '.join-bg', '.join-page', '.join-card', '.join-pin', '.join-nick', '.join-footer', 'END PLAYER SCREENS'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
