<?php

namespace Tests\Feature;

use App\Models\GamePlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class PlayerFinalRedesignTest extends TestCase
{
    use RefreshDatabase, MakesPlayerGame;

    private function html(): string
    {
        ['game' => $game, 'player' => $player] = $this->makePlayerGame('finished', '777002', ['score' => 1200]);
        GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Rival', 'score' => 2000, 'streak' => 0, 'best_streak' => 4]);
        GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Third', 'score' => 300, 'streak' => 0, 'best_streak' => 0]);

        return $this->withSession(['player_id_777002' => $player->id])
            ->get(route('play.final', '777002'))->assertOk()->getContent();
    }

    public function test_results_page_renders_podium_leaderboard_and_actions(): void
    {
        $html = $this->html();
        foreach ([
            'id="suspense-overlay"', 'id="main-results" class="results hidden"', 'class="podium"',
            'podium-1', 'podium-2', 'podium-3', 'Rival', 'Third', 'P1',
            'lb-rank-1', 'lb-me', 'lb-you-badge', 'Play again',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_the_players_own_row_is_highlighted_once(): void
    {
        $this->assertSame(1, substr_count($this->html(), 'lb-me'));
    }

    public function test_final_view_has_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/play/final.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('play/final.blade.php');
    }

    /** Calm mode must not fire confetti. */
    public function test_confetti_only_fires_in_party_mode(): void
    {
        $src = file_get_contents(resource_path('views/play/final.blade.php'));
        $this->assertStringContainsString("QB.UI.fx() === 'party'", $src);
        $this->assertStringNotContainsString('QB.Confetti.burst(', str_replace('QB.Confetti.burst(n)', '', $src));
    }

    public function test_confetti_uses_the_new_palette(): void
    {
        $js = file_get_contents(public_path('js/confetti.js'));
        foreach (['#7c5cff', '#fbbf24', '#ff5d73', '#22d3ee', '#a3e635'] as $color) {
            $this->assertStringContainsString($color, $js);
        }
        $this->assertStringNotContainsString('#46178f', $js);
    }

    public function test_results_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            '.suspense-overlay', '.suspense-content', '.results', '.result-stats', '.podium-col', '.podium-block',
            '.perf-row', '.perf-mark', '.result-actions', '.leaderboard-item.lb-rank-1', '.leaderboard-item.lb-me',
            '.lb-you-badge', '.lb-streak-badge', '@keyframes podium-rise', '@keyframes suspense-in', '@keyframes suspense-out',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }

    public function test_results_column_is_full_width_so_it_cannot_widen_the_page(): void
    {
        // In the game shell's column flexbox a margin:0 auto item shrink-wraps its content and
        // overflowed a 390px phone by 4px; width:100% keeps it inside the viewport.
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertMatchesRegularExpression('/\.results\{[^}]*width:100%/', $css);
    }

    public function test_new_leaderboard_name_rule_overrides_the_legacy_uppercase_style(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString(
            '.leaderboard-name{flex:1;min-width:0;font-weight:700;text-transform:none;letter-spacing:normal;font-size:1rem;overflow-wrap:anywhere;word-break:break-word}',
            $css
        );
    }

    public function test_every_overflow_wrap_anywhere_has_a_word_break_fallback(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertGreaterThanOrEqual(4, substr_count($css, 'overflow-wrap:anywhere'));
        $this->assertSame(substr_count($css, 'overflow-wrap:anywhere'), substr_count($css, 'overflow-wrap:anywhere;word-break:break-word'));
    }
}
