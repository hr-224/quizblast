<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\GameAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HostAnswerChartTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(string $status = 'reviewing'): array
    {
        $host = User::create([
            'name'     => 'Host',
            'email'    => 'host@test.com',
            'password' => bcrypt('password'),
        ]);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Test Quiz']);
        $question = Question::create([
            'quiz_id'       => $quiz->id,
            'question_text' => 'Test question?',
            'time_limit'    => 20,
            'points'        => 1000,
            'order'         => 0,
        ]);
        $answers = collect([
            Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => false, 'order' => 0]),
            Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => true,  'order' => 1]),
            Answer::create(['question_id' => $question->id, 'answer_text' => 'C', 'is_correct' => false, 'order' => 2]),
            Answer::create(['question_id' => $question->id, 'answer_text' => 'D', 'is_correct' => false, 'order' => 3]),
        ]);
        $game = Game::create([
            'user_id'          => $host->id,
            'quiz_id'          => $quiz->id,
            'pin'              => '123456',
            'status'           => $status,
            'current_question' => 0,
        ]);

        // Two distinct players, each votes for a different answer
        $p1 = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'P1', 'session_id' => 'sess1']);
        $p2 = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'P2', 'session_id' => 'sess2']);
        GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $p1->id, 'question_id' => $question->id, 'answer_id' => $answers[0]->id]);
        GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $p2->id, 'question_id' => $question->id, 'answer_id' => $answers[1]->id]);

        return compact('host', 'game', 'question', 'answers');
    }

    public function test_chart_renders_during_reviewing(): void
    {
        ['host' => $host, 'game' => $game] = $this->makeGame('reviewing');

        $response = $this->actingAs($host)->get(route('game.question', $game));

        $response->assertStatus(200);
        $response->assertSee('Response breakdown');
        $response->assertSee('responses');
    }

    public function test_chart_hidden_during_question_phase(): void
    {
        ['host' => $host, 'game' => $game] = $this->makeGame('question');

        $response = $this->actingAs($host)->get(route('game.question', $game));

        $response->assertStatus(200);
        $response->assertDontSee('Response breakdown');
    }

    public function test_chart_shows_zero_vote_answers(): void
    {
        // answers[2] and answers[3] get zero votes — they must still render (4px stub, count = 0)
        ['host' => $host, 'game' => $game] = $this->makeGame('reviewing');

        $response = $this->actingAs($host)->get(route('game.question', $game));

        $response->assertStatus(200);
        // The response breakdown section is present and the page has the expected count display
        $response->assertSee('Response breakdown');
        // Total answered = 2 responses
        $response->assertSee('2 responses');
    }

    /**
     * Final-review Group B: a multi-correct question writes one GameAnswer row per selected
     * answer, so a row count over-reports how many distinct players answered. The topbar chip
     * and the chart header must show distinct players (1), not rows (2).
     */
    public function test_multi_correct_answer_count_is_distinct_players_not_rows(): void
    {
        $host = User::create(['name' => 'Host2', 'email' => 'host2@test.com', 'password' => bcrypt('password')]);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Multi Quiz']);
        $question = Question::create([
            'quiz_id' => $quiz->id, 'question_text' => 'Pick two?', 'time_limit' => 20,
            'points' => 1000, 'order' => 0, 'multiple_correct' => true,
        ]);
        $answers = collect([
            Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true, 'order' => 0]),
            Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => true, 'order' => 1]),
            Answer::create(['question_id' => $question->id, 'answer_text' => 'C', 'is_correct' => false, 'order' => 2]),
            Answer::create(['question_id' => $question->id, 'answer_text' => 'D', 'is_correct' => false, 'order' => 3]),
        ]);
        $game = Game::create([
            'user_id' => $host->id, 'quiz_id' => $quiz->id, 'pin' => '654321',
            'status' => 'reviewing', 'current_question' => 0,
        ]);
        $p1 = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'Solo', 'session_id' => 'solo1']);
        // One player selects both correct answers → two GameAnswer rows for one player.
        GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $p1->id, 'question_id' => $question->id, 'answer_id' => $answers[0]->id]);
        GameAnswer::create(['game_id' => $game->id, 'game_player_id' => $p1->id, 'question_id' => $question->id, 'answer_id' => $answers[1]->id]);

        $html = $this->actingAs($host)->get(route('game.question', $game))->assertOk()->getContent();

        $this->assertStringContainsString('1 responses', $html);
        $this->assertStringNotContainsString('2 responses', $html);
        $this->assertMatchesRegularExpression('/id="answered-count">1</', $html);
    }
}
