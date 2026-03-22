<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Game;
use App\Models\GamePlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiCorrectReviewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function game_blade_contains_partial_credit_verdict_logic(): void
    {
        $host = User::create([
            'name' => 'Host', 'email' => 'host2@test.com', 'password' => bcrypt('pw'),
        ]);
        $quiz     = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $question = Question::create([
            'quiz_id' => $quiz->id, 'question_text' => 'Multi?',
            'time_limit' => 20, 'points' => 1000, 'order' => 0, 'multiple_correct' => true,
        ]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true,  'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => true,  'order' => 1]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'C', 'is_correct' => false, 'order' => 2]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'D', 'is_correct' => false, 'order' => 3]);
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id,
            'pin' => '777001', 'status' => 'question', 'current_question' => 0,
        ]);
        $player = GamePlayer::create([
            'game_id' => $game->id, 'nickname' => 'P1',
            'score' => 0, 'streak' => 0, 'best_streak' => 0,
        ]);
        session(['player_id_777001' => $player->id]);

        $response = $this->get("/play/777001/game");

        $response->assertOk();
        $response->assertSee('Partial Credit!', false);
        $response->assertSee('review-verdict-partial', false);
    }
}
