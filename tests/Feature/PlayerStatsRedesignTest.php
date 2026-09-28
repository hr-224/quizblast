<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerStatsRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::create(['name' => 'Stat Fan', 'email' => 'stats@test.com', 'password' => 'secret-pass']);
        $user->forceFill(['total_score' => 4500, 'games_played' => 3, 'wins' => 1])->save();
        return $user;
    }

    public function test_stats_page_requires_login(): void
    {
        $this->get(route('player.stats'))->assertRedirect(route('login'));
    }

    public function test_stats_page_shows_summary_and_logout(): void
    {
        $user = $this->makeUser();
        $html = $this->actingAs($user)->get(route('player.stats'))->assertOk()->getContent();

        foreach (['Stat Fan', 'stats@test.com', '3', '1', '33%', '4,500', 'data-confirm', route('logout'), 'No games played yet'] as $needle) {
            $this->assertStringContainsString((string) $needle, $html, (string) $needle);
        }
    }

    /** A brand new account (all stat defaults, no games played) must render cleanly —
     *  no division-by-zero, correct zero values, and the empty-state message. */
    public function test_stats_page_renders_correctly_for_a_brand_new_account(): void
    {
        $user = User::create(['name' => 'Brand New', 'email' => 'brand-new@test.com', 'password' => 'secret-pass']);
        $html = $this->actingAs($user)->get(route('player.stats'))->assertOk()->getContent();

        foreach (['Brand New', 'brand-new@test.com', '0%', 'No games played yet'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_stats_page_lists_recent_games(): void
    {
        $user = $this->makeUser();
        $host = User::create(['name' => 'Host', 'email' => 'gp@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Played Quiz']);
        $game = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '950001', 'status' => 'finished', 'current_question' => 0]);
        GamePlayer::create(['game_id' => $game->id, 'nickname' => 'StatFan1', 'score' => 800, 'streak' => 0, 'best_streak' => 4, 'user_id' => $user->id]);

        $html = $this->actingAs($user)->get(route('player.stats'))->getContent();

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
        $user = $this->makeUser();
        $html = $this->actingAs($user)->get(route('player.stats'))->getContent();
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
