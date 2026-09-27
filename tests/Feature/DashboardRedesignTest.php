<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GamePlayer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_quizzes_and_recent_games(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'dash@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Dash Quiz', 'is_public' => true]);
        Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '700001', 'status' => 'finished', 'current_question' => 0]);

        $html = $this->actingAs($host)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['Dash Quiz', 'PIN 700001', 'Public', route('quizzes.create')] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertStringNotContainsString('onsubmit=', $html);
    }

    public function test_dashboard_empty_state(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'dash2@test.com', 'password' => 'secret-pass']);
        $html = $this->actingAs($host)->get(route('dashboard'))->getContent();
        $this->assertStringContainsString('No quizzes yet', $html);
    }

    public function test_game_history_uses_answer_chip_and_escapes_html(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'hist@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Hist Quiz']);
        $question = Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => '<b>bold</b>', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'plain', 'is_correct' => false, 'order' => 1]);
        $game = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '700002', 'status' => 'finished', 'current_question' => 0]);
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'P1', 'score' => 1000, 'streak' => 0, 'best_streak' => 0]);
        GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $player->id, 'question_id' => $question->id, 'answer_id' => Answer::first()->id, 'points_earned' => 1000]);

        $html = $this->actingAs($host)->get(route('dashboard.history', $game))->assertOk()->getContent();

        $this->assertStringContainsString('class="ans-chip is-correct"', $html);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringContainsString('P1', $html);
    }

    public function test_no_inline_styles_on_dashboard_views(): void
    {
        foreach (['dashboard/index.blade.php', 'dashboard/history.blade.php'] as $file) {
            $src = file_get_contents(resource_path('views/' . $file));
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src, $file);
        }
    }

    public function test_pagination_renders_real_page_links(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'dash4@test.com', 'password' => 'secret-pass']);
        for ($i = 0; $i < 21; $i++) {
            Quiz::create(['user_id' => $host->id, 'title' => "Quiz {$i}"]);
        }
        $html = $this->actingAs($host)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('class="pagination"', $html);
        $this->assertMatchesRegularExpression('/<a[^>]*href="[^"]*page=2[^"]*"[^>]*>\s*2\s*<\/a>/', $html);
    }

    public function test_delete_quiz_button_uses_data_confirm(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'dash3@test.com', 'password' => 'secret-pass']);
        Quiz::create(['user_id' => $host->id, 'title' => 'To delete']);
        $html = $this->actingAs($host)->get(route('dashboard'))->getContent();
        $this->assertMatchesRegularExpression('/data-confirm="[^"]*[Dd]elete[^"]*"/', $html);
        $this->assertStringNotContainsString('onsubmit=', $html);
    }
}
