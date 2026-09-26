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
}
