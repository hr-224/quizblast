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
use App\Events\GameStateChanged;
use App\Events\AnswerCountUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiCorrectBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function makeMultiGame(): array
    {
        $host = User::create([
            'name' => 'Host', 'email' => 'host@test.com',
            'password' => bcrypt('pw'),
        ]);
        $quiz     = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        $question = Question::create([
            'quiz_id'          => $quiz->id,
            'question_text'    => 'Pick all correct',
            'time_limit'       => 20,
            'points'           => 1000,
            'order'            => 0,
            'multiple_correct' => true,
        ]);
        $a1 = Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true,  'order' => 0]);
        $a2 = Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => true,  'order' => 1]);
        $a3 = Answer::create(['question_id' => $question->id, 'answer_text' => 'C', 'is_correct' => false, 'order' => 2]);
        $a4 = Answer::create(['question_id' => $question->id, 'answer_text' => 'D', 'is_correct' => false, 'order' => 3]);
        $game = Game::create([
            'quiz_id'          => $quiz->id,
            'user_id'          => $host->id,
            'pin'              => '999001',
            'status'           => 'question',
            'current_question' => 0,
        ]);
        $player = GamePlayer::create([
            'game_id' => $game->id, 'nickname' => 'P1', 'score' => 0,
            'streak' => 0, 'best_streak' => 0,
        ]);
        return compact('game', 'question', 'player', 'a1', 'a2', 'a3', 'a4');
    }

    /** @test */
    public function game_state_changed_includes_multiple_correct_in_question_payload(): void
    {
        ['game' => $game] = $this->makeMultiGame();

        $payload = (new GameStateChanged($game))->broadcastWith();

        $this->assertArrayHasKey('multiple_correct', $payload['question']);
        $this->assertTrue($payload['question']['multiple_correct']);
    }

    /** @test */
    public function game_state_changed_includes_multiple_correct_false_for_single_questions(): void
    {
        $host  = User::create(['name' => 'H2', 'email' => 'h2@test.com', 'password' => bcrypt('pw')]);
        $quiz  = Quiz::create(['user_id' => $host->id, 'title' => 'Q2']);
        $q     = Question::create([
            'quiz_id' => $quiz->id, 'question_text' => 'Single?',
            'time_limit' => 20, 'points' => 1000, 'order' => 0,
            'multiple_correct' => false,
        ]);
        Answer::create(['question_id' => $q->id, 'answer_text' => 'A', 'is_correct' => true,  'order' => 0]);
        Answer::create(['question_id' => $q->id, 'answer_text' => 'B', 'is_correct' => false, 'order' => 1]);
        Answer::create(['question_id' => $q->id, 'answer_text' => 'C', 'is_correct' => false, 'order' => 2]);
        Answer::create(['question_id' => $q->id, 'answer_text' => 'D', 'is_correct' => false, 'order' => 3]);
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id,
            'pin' => '999002', 'status' => 'question', 'current_question' => 0,
        ]);

        $payload = (new GameStateChanged($game))->broadcastWith();

        $this->assertFalse($payload['question']['multiple_correct']);
    }

    /** @test */
    public function answer_count_updated_counts_distinct_players_not_rows(): void
    {
        ['game' => $game, 'question' => $question, 'player' => $player, 'a1' => $a1, 'a2' => $a2] = $this->makeMultiGame();

        // Player submits two answer rows (multi-select) — should count as 1 player
        GameAnswer::create([
            'game_id' => $game->id, 'game_player_id' => $player->id,
            'question_id' => $question->id, 'answer_id' => $a1->id,
            'response_time_ms' => 3000, 'points_earned' => 0,
        ]);
        GameAnswer::create([
            'game_id' => $game->id, 'game_player_id' => $player->id,
            'question_id' => $question->id, 'answer_id' => $a2->id,
            'response_time_ms' => 3000, 'points_earned' => 0,
        ]);

        $payload = (new AnswerCountUpdated($game, $question->id))->broadcastWith();

        $this->assertSame(1, $payload['total_answered']);
    }

    /** @test */
    public function game_answer_stores_power_up_used_and_streak_bonus(): void
    {
        ['game' => $game, 'question' => $question, 'player' => $player, 'a1' => $a1] = $this->makeMultiGame();

        $row = GameAnswer::create([
            'game_id'          => $game->id,
            'game_player_id'   => $player->id,
            'question_id'      => $question->id,
            'answer_id'        => $a1->id,
            'response_time_ms' => 2000,
            'points_earned'    => 800,
            'power_up_used'    => 'double_points',
            'streak_bonus'     => 80,
        ]);

        $this->assertSame('double_points', $row->fresh()->power_up_used);
        $this->assertEquals(80, $row->fresh()->streak_bonus);
    }
}
