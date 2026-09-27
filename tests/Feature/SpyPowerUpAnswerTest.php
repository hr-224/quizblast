<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpyPowerUpAnswerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    /** A player who used Spy must still be able to submit an answer; it must not 422. */
    public function test_submitting_an_answer_with_the_spy_power_up_is_accepted_and_scored(): void
    {
        $host     = User::create(['name' => 'Host', 'email' => 'spy@test.com', 'password' => 'secret-pass']);
        $quiz     = Quiz::create(['user_id' => $host->id, 'title' => 'Spy Quiz']);
        $question = Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);
        $correct  = Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => false, 'order' => 1]);
        $game   = Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '910001', 'status' => 'question', 'current_question' => 0]);
        $player = GamePlayer::create(['game_id' => $game->id, 'nickname' => 'P1', 'score' => 0, 'streak' => 0, 'best_streak' => 0, 'power_ups' => ['spy']]);
        session(['player_id_910001' => $player->id]);

        $response = $this->post('/play/910001/answer', [
            'answer_ids'       => [$correct->id],
            'question_id'      => $question->id,
            'response_time_ms' => 4000,
            'power_up'         => 'spy',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['correct' => true]);
        $this->assertGreaterThan(0, $response->json('points_earned'), 'spy applies a 0.6 penalty, not zero — the answer must still score');
        // scoring is base * speedFactor * 0.6; response_time_ms/time_limit_ms is well inside the window, so the value is well above zero.
    }
}
