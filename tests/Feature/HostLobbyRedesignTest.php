<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesHostGame;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class HostLobbyRedesignTest extends TestCase
{
    use RefreshDatabase, MakesHostGame, MakesPlayerGame;

    private function html(int $players = 2, string $pin = '555001'): string
    {
        ['host' => $host, 'game' => $game] = $this->makeHostGame('waiting', 2, $players, $pin);
        return $this->actingAs($host)->get(route('game.lobby', $game))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/host/lobby.blade.php'));
    }

    public function test_lobby_shows_the_giant_pin_join_hint_and_all_hooks(): void
    {
        $html = $this->html();
        foreach ([
            'class="pin-hero"', '555001', 'Join at', 'id="player-grid"', 'id="launch-btn"', 'id="count-num"',
            'id="ws-dot"', 'id="kick-confirm"', 'id="kick-name"', 'id="kick-yes"', 'id="kick-no"',
            'Start game', '(2 questions)', 'Player1', 'Player2',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_launch_button_is_disabled_only_when_nobody_has_joined(): void
    {
        $this->assertMatchesRegularExpression('/id="launch-btn"[^>]*\sdisabled/', $this->html(0));
        $this->assertDoesNotMatchRegularExpression('/id="launch-btn"[^>]*\sdisabled/', $this->html(2, '555002'));
    }

    public function test_join_address_comes_from_the_app_url_not_a_hardcoded_domain(): void
    {
        $this->assertStringNotContainsString('quizblast.ultmods.com', $this->src());
        $this->assertStringContainsString("url('/')", $this->src());
    }

    public function test_lobby_has_no_inline_styles_or_handlers(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style|\son(click|submit)\s*=/i', $this->src());
    }

    public function test_lobby_script_is_modern_but_avoids_optional_chaining_and_legacy_controls(): void
    {
        $src = $this->src();
        preg_match('/<script>\s*\(function.*?<\/script>/s', $src, $m);
        $js = $m[0] ?? '';
        $this->assertNotSame('', $js, 'inline script not found');
        $this->assertStringNotContainsString('?.', $js);
        $this->assertStringNotContainsString('??', $js);
        $this->assertStringNotContainsString('createControls', $src);   // the top-bar mute replaces the floating buttons
        $this->assertStringNotContainsString('addslashes', $src);
    }

    /** Review focus 1: js.pusher.com blocked must not stop polling. */
    public function test_pusher_is_optional_and_polling_uses_the_players_endpoint(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('window.Pusher', $src);
        $this->assertMatchesRegularExpression('/try\s*\{[^}]*new Pusher/s', $src);
        $this->assertStringContainsString("'/api/game/' + pin + '/players'", $src);
        // the poll interval is registered after (outside) the Pusher try/catch, so it runs even when Pusher is missing
        $this->assertGreaterThan(strpos($src, 'pusher = null;'), strpos($src, 'setInterval'));
    }

    /** Review focus 2: nicknames are text, never HTML/JS-interpolated. */
    public function test_nicknames_are_inserted_as_text_and_seeded_with_json(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('chip.textContent = p.nickname', $src);
        $this->assertStringNotContainsString('innerHTML', $src);
        $this->assertStringContainsString('@json(', $src);
    }

    /** Review focus 5: the pop-over is positioned from the chip's viewport rect. */
    public function test_kick_popover_uses_viewport_coordinates(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('getBoundingClientRect', $src);
        $this->assertStringNotContainsString('pageX', $src);
        $this->assertStringContainsString("'/play/' + pin + '/kick/'", $src);
        $this->assertStringContainsString("method: 'DELETE'", $src);
    }

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('host/lobby.blade.php');
    }

    public function test_lobby_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            '7. HOST SCREENS', 'END HOST SCREENS', '.host-lobby', '.pin-hero', '.qr-card', '.host-chips',
            '.host-chip', '.is-leaving', '.kick-confirm', '@keyframes chip-in',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
