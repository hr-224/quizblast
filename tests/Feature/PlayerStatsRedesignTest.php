<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\PlayerAccount;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerStatsRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): PlayerAccount
    {
        return PlayerAccount::create([
            'name' => 'Stat Fan', 'email' => 'stats@test.com', 'password' => 'secret-pass',
            'total_score' => 4500, 'games_played' => 3, 'wins' => 1,
        ]);
    }

    public function test_stats_page_shows_summary_and_logout(): void
    {
        $account = $this->makeAccount();
        $html = $this->withSession(['player_account_id' => $account->id])->get(route('player.stats'))->assertOk()->getContent();

        foreach (['Stat Fan', 'stats@test.com', '3', '1', '33%', '4,500', 'data-confirm', route('player.logout'), 'No games played yet'] as $needle) {
            $this->assertStringContainsString((string) $needle, $html, (string) $needle);
        }
    }

    public function test_stats_page_lists_recent_games(): void
    {
        $account = $this->makeAccount();
        $host = User::create(['name' => 'Host', 'email' => 'gp@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Played Quiz']);
        $game = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '950001', 'status' => 'finished', 'current_question' => 0]);
        GamePlayer::create(['game_id' => $game->id, 'nickname' => 'StatFan1', 'score' => 800, 'streak' => 0, 'best_streak' => 4, 'player_account_id' => $account->id]);

        $html = $this->withSession(['player_account_id' => $account->id])->get(route('player.stats'))->getContent();

        foreach (['Played Quiz', 'StatFan1', '800', '4'] as $needle) {
            $this->assertStringContainsString((string) $needle, $html, (string) $needle);
        }
        $this->assertStringNotContainsString('No games played yet', $html);
    }

    public function test_no_inline_styles(): void
    {
        $src = file_get_contents(resource_path('views/auth/player-stats.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
    }

    public function test_logout_uses_data_confirm_not_a_bare_form(): void
    {
        $account = $this->makeAccount();
        $html = $this->withSession(['player_account_id' => $account->id])->get(route('player.stats'))->getContent();
        $this->assertMatchesRegularExpression('/data-confirm="[^"]+"/', $html);
    }

    public function test_stats_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.stats-hero', '.stats-summary', '.stats-stat', '.stats-stat-val'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
