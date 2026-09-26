<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    use RefreshDatabase;

    private function gameShellResponse()
    {
        $host = User::create(['name' => 'Host', 'email' => 'shell@test.com', 'password' => bcrypt('pw')]);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $game = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '777001', 'status' => 'waiting', 'current_question' => 0]);
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'P1', 'score' => 0, 'streak' => 0, 'best_streak' => 0]);

        return $this->withSession(['player_id_777001' => $player->id])->get(route('play.lobby', '777001'));
    }

    public function test_site_shell_has_fx_bootstrap_toggle_and_ui_script(): void
    {
        $html = $this->get(route('play.join'))->assertStatus(200)->getContent();
        $this->assertStringContainsString("data-fx", $html);
        $this->assertStringContainsString("qb-fx", $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
        $this->assertStringContainsString('data-fx-toggle', $html);
        $this->assertStringContainsString('/js/qb-ui.js', $html);
        $this->assertStringNotContainsString('data-mute-toggle', $html);
    }

    public function test_game_shell_has_fx_toggle_mute_toggle_and_ui_script(): void
    {
        $html = $this->gameShellResponse()->assertStatus(200)->getContent();
        $this->assertStringContainsString('qb-fx', $html);
        $this->assertStringContainsString('data-fx-toggle', $html);
        $this->assertStringContainsString('data-mute-toggle', $html);
        $this->assertStringContainsString('/js/qb-ui.js', $html);
        $this->assertStringContainsString('class="topbar-right"', $html);
    }

    public function test_wordmark_is_one_word_in_both_shells_with_scoped_violet_css(): void
    {
        foreach ([$this->get(route('play.join')), $this->gameShellResponse()] as $response) {
            $this->assertStringContainsString('<span class="brand-word">Quiz<span>Blast</span></span>', $response->getContent());
        }

        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.navbar-brand .brand-word span', $css);
        $this->assertStringContainsString('.game-topbar-brand .brand-word span', $css);
        $this->assertStringNotContainsString('.navbar-brand span{', $css);
        $this->assertStringNotContainsString('.game-topbar-brand span{', $css);
    }

    public function test_both_shells_preload_fonts_and_make_no_third_party_font_requests(): void
    {
        foreach ([$this->get(route('play.join')), $this->gameShellResponse()] as $response) {
            $html = $response->getContent();
            $this->assertStringContainsString('rel="preload" href="/fonts/lexend-latin.woff2"', $html);
            $this->assertStringContainsString('rel="preload" href="/fonts/atkinson-400-latin.woff2"', $html);
            $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        }
    }

    /** Review focus: localStorage may throw (private mode / locked-down browsers). */
    public function test_fx_bootstrap_survives_blocked_storage(): void
    {
        $html = $this->get(route('play.join'))->getContent();
        $this->assertMatchesRegularExpression('/<script>\(function\(\)\{try\{.*qb-fx.*\}catch\(e\)\{/s', $html);
        $ui = file_get_contents(public_path('js/qb-ui.js'));
        $this->assertStringContainsString('try { return window.localStorage.getItem', $ui);
        $this->assertStringContainsString('try { window.localStorage.setItem', $ui);
    }

    /** Review focus: a muted user must stay muted when sounds.js loads. */
    public function test_sounds_js_reads_persisted_mute_before_any_audio_state(): void
    {
        $src = file_get_contents(public_path('js/sounds.js'));
        $declPos = strpos($src, 'mus=true, sfxOn=true');
        $readPos = strpos($src, "getItem('qb-muted')");
        $this->assertNotFalse($readPos, 'sounds.js does not read qb-muted');
        $this->assertGreaterThan($declPos, $readPos);
        $this->assertLessThan(strpos($src, 'function init()'), $readPos);
        $this->assertStringContainsString('setMuted', $src);
    }

    public function test_navbar_hooks_used_by_existing_tests_are_intact(): void
    {
        $html = $this->get(route('play.join'))->getContent();
        foreach (['id="nav-toggle"', 'id="nav-dropdown"', 'class="hamburger-btn"', 'id="nav-links"', 'dropdown.hidden'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }
}
