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

class MultiCorrectScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function makeGame(): array
    {
        $host = User::create([
            'name' => 'Host', 'email' => 'host@test.com', 'password' => bcrypt('pw'),
        ]);
        $quiz     = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $question = Question::create([
            'quiz_id' => $quiz->id, 'question_text' => 'Multi?',
            'time_limit' => 20, 'points' => 1000, 'order' => 0,
            'multiple_correct' => true,
        ]);
        $a1 = Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true,  'order' => 0]);
        $a2 = Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => true,  'order' => 1]);
        $a3 = Answer::create(['question_id' => $question->id, 'answer_text' => 'C', 'is_correct' => false, 'order' => 2]);
        $a4 = Answer::create(['question_id' => $question->id, 'answer_text' => 'D', 'is_correct' => false, 'order' => 3]);
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id,
            'pin' => '888001', 'status' => 'question', 'current_question' => 0,
        ]);
        $player = GamePlayer::create([
            'game_id' => $game->id, 'nickname' => 'P1',
            'score' => 0, 'streak' => 0, 'best_streak' => 0,
        ]);
        session(['player_id_888001' => $player->id]);
        return compact('game', 'question', 'player', 'a1', 'a2', 'a3', 'a4');
    }

    /** @test */
    public function submitting_all_correct_answers_returns_correct_true(): void
    {
        ['question' => $q, 'a1' => $a1, 'a2' => $a2] = $this->makeGame();

        $response = $this->postJson('/play/888001/answer', [
            'answer_ids'       => [$a1->id, $a2->id],
            'question_id'      => $q->id,
            'response_time_ms' => 5000,
        ]);

        $response->assertOk()->assertJson(['correct' => true]);
        $this->assertGreaterThan(0, $response->json('points_earned'));
    }

    /** @test */
    public function submitting_one_of_two_correct_answers_returns_partial(): void
    {
        ['question' => $q, 'a1' => $a1] = $this->makeGame();

        $response = $this->postJson('/play/888001/answer', [
            'answer_ids'       => [$a1->id],
            'question_id'      => $q->id,
            'response_time_ms' => 5000,
        ]);

        $response->assertOk()->assertJson(['correct' => 'partial']);
        $this->assertGreaterThan(0, $response->json('points_earned'));
    }

    /** @test */
    public function submitting_partial_with_wrong_answer_reduces_points(): void
    {
        ['question' => $q, 'a1' => $a1, 'a3' => $a3] = $this->makeGame();

        // 1 correct, 1 wrong → fraction = max(0, 1-1)/2 = 0 → no credit
        $response = $this->postJson('/play/888001/answer', [
            'answer_ids'       => [$a1->id, $a3->id],
            'question_id'      => $q->id,
            'response_time_ms' => 5000,
        ]);

        $response->assertOk()->assertJson(['correct' => false]);
        $this->assertSame(0, $response->json('points_earned'));
    }

    /** @test */
    public function submitting_only_wrong_answers_returns_false_no_points(): void
    {
        ['question' => $q, 'a3' => $a3, 'a4' => $a4] = $this->makeGame();

        $response = $this->postJson('/play/888001/answer', [
            'answer_ids'       => [$a3->id, $a4->id],
            'question_id'      => $q->id,
            'response_time_ms' => 5000,
        ]);

        $response->assertOk()->assertJson(['correct' => false]);
        $this->assertSame(0, $response->json('points_earned'));
    }

    /** @test */
    public function partial_credit_points_are_proportional(): void
    {
        ['question' => $q, 'a1' => $a1, 'a2' => $a2] = $this->makeGame();

        // 1 of 2 correct, no wrong = 50% points (response_time_ms=0 → max speed factor)
        $partial = $this->postJson('/play/888001/answer', [
            'answer_ids' => [$a1->id], 'question_id' => $q->id, 'response_time_ms' => 0,
        ]);

        // Reset and submit all correct
        GameAnswer::query()->delete();
        GamePlayer::find(session('player_id_888001'))->update(['streak' => 0, 'score' => 0]);

        $full = $this->postJson('/play/888001/answer', [
            'answer_ids' => [$a1->id, $a2->id],
            'question_id' => $q->id, 'response_time_ms' => 0,
        ]);

        // Partial should be approximately half of full (within ±1 for integer rounding)
        $this->assertGreaterThan(0, $partial->json('points_earned'));
        $this->assertEqualsWithDelta($full->json('points_earned') / 2, $partial->json('points_earned'), 1.0);
    }

    /** @test */
    public function partial_verdict_resets_streak_to_zero(): void
    {
        ['question' => $q, 'a1' => $a1, 'player' => $player] = $this->makeGame();
        $player->update(['streak' => 5]);

        $response = $this->postJson('/play/888001/answer', [
            'answer_ids'       => [$a1->id],
            'question_id'      => $q->id,
            'response_time_ms' => 5000,
        ]);

        $response->assertOk()->assertJson(['correct' => 'partial', 'streak' => 0]);
    }

    /** @test */
    public function partial_verdict_earns_no_streak_bonus(): void
    {
        ['question' => $q, 'a1' => $a1, 'player' => $player] = $this->makeGame();
        $player->update(['streak' => 5]);

        $response = $this->postJson('/play/888001/answer', [
            'answer_ids'       => [$a1->id],
            'question_id'      => $q->id,
            'response_time_ms' => 0,
        ]);

        $response->assertOk()->assertJson(['correct' => 'partial', 'streak_bonus' => 0]);
    }
}
