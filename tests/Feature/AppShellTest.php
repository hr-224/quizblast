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
        $this->assertStringContainsString("setAttribute('data-fx'", $html);
        $this->assertStringContainsString("qb-fx", $html);
        // Party is the default unless the visitor has an explicit stored preference.
        $this->assertMatchesRegularExpression("/f!=='calm'&&f!=='party'\\)\\{f='party';\\}/", $html);
        // The bootstrap must run before the stylesheet loads so there is no flash of the wrong mode.
        $scriptPos = strpos($html, "setAttribute('data-fx'");
        $cssPos = strpos($html, '/css/app.css');
        $this->assertNotFalse($scriptPos);
        $this->assertNotFalse($cssPos);
        $this->assertLessThan($cssPos, $scriptPos);
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
        // Storage is read in its own try/catch so a throw still reaches the party default below it.
        $this->assertMatchesRegularExpression('/<script>\(function\(\)\{var f=null;try\{f=localStorage\.getItem\(\'qb-fx\'\);\}catch\(e\)\{\}try\{.*f=\'party\';.*\}catch\(e\)\{\}\}\)\(\);<\/script>/s', $html);
        $this->assertStringNotContainsString("catch(e){document.documentElement.setAttribute('data-fx','calm')", $html);
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

    /** sounds.js is cached for 30 days in production, so every loader must carry a content-based version. */
    public function test_no_view_pins_sounds_js_to_a_literal_version(): void
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            // play/game.blade.php is rewritten in Phase 2; it is excluded from this Phase 1 check.
            if (str_ends_with(str_replace('\\', '/', $file->getPathname()), 'play/game.blade.php')) {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            if (preg_match('#sounds\.js\?v=\d+#', $src)) {
                $offenders[] = $file->getPathname();
            }
            if (preg_match('#/js/(sounds|confetti)\.js["\']#', $src)) {
                $offenders[] = $file->getPathname() . ' (unversioned)';
            }
        }
        $this->assertSame([], $offenders);
    }

    public function test_calm_mode_exempts_emoji_overlays_from_animation_shortcut(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('html[data-fx="calm"] #emoji-overlay > *', $css);
        $this->assertStringContainsString('html[data-fx="calm"] #spec-emoji-overlay > *', $css);
        $this->assertMatchesRegularExpression('/#spec-emoji-overlay > \*\{animation:none!important\}/', $css);
    }

    public function test_sounds_js_restarts_lobby_music_on_unmute(): void
    {
        $src = file_get_contents(public_path('js/sounds.js'));
        $this->assertStringContainsString('wantBg', $src);
        $this->assertMatchesRegularExpression('/function playLobbyMusic\(\)\{\s*wantBg=true;/', $src);
        $this->assertMatchesRegularExpression('/setMuted:\s*function\(m\)\{[^}]*if\(m\) stopBg\(\);[^}]*wantBg[^}]*playLobbyMusic\(\)/', $src);
    }

    public function test_toggles_use_fixed_names_with_aria_pressed_carrying_state(): void
    {
        $html = $this->gameShellResponse()->getContent();
        $this->assertStringContainsString('data-fx-toggle aria-pressed="true" aria-label="Party effects"', $html);
        $this->assertStringContainsString('data-mute-toggle aria-pressed="true" aria-label="Sound"', $html);

        $ui = file_get_contents(public_path('js/qb-ui.js'));
        $this->assertStringNotContainsString("setAttribute('aria-label'", $ui);
    }

    public function test_shell_logout_forms_and_signup_link_have_no_inline_styles(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringNotContainsString('style="', $layout);
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.inline-form{display:inline}', $css);
        $this->assertStringContainsString('.nav-dropdown-link-accent', $css);
        $this->assertStringContainsString('nav-dropdown-link-accent', $layout);
    }

    public function test_font_licence_text_is_shipped(): void
    {
        $ofl = file_get_contents(public_path('fonts/OFL.txt'));
        $this->assertStringContainsString('SIL OPEN FONT LICENSE Version 1.1', $ofl);
        $this->assertStringContainsString('Lexend Project Authors', $ofl);
        $this->assertStringContainsString('Braille Institute of America', $ofl);
    }

    public function test_clamp_font_size_has_a_plain_fallback(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.ans-tile .ans-shape{font-size:3rem;font-size:clamp(2.5rem,10vw,4.5rem)}', $css);
    }
}
