<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTimeRemainingTest extends TestCase
{
    use RefreshDatabase;

    private function makeGame(int $timeLimit, ?\Illuminate\Support\Carbon $startedAt, string $status = 'question'): Game
    {
        $host = User::create(['name' => 'Host', 'email' => 'timer@test.com', 'password' => bcrypt('pw')]);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Q']);
        Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => $timeLimit, 'points' => 1000, 'order' => 0]);

        return Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '900001',
            'status' => $status, 'current_question' => 0, 'question_started_at' => $startedAt,
        ]);
    }

    /** A question started 7s ago with a 20s limit has 13s left, not 27s. */
    public function test_time_remaining_subtracts_elapsed_time_not_adds_it(): void
    {
        $game = $this->makeGame(20, now()->subSeconds(7));

        $remaining = $game->timeRemaining();

        // ~13s left (20 - 7), allowing a little slack for the time the test itself takes to run.
        $this->assertGreaterThanOrEqual(11, $remaining);
        $this->assertLessThanOrEqual(13, $remaining, 'remaining time must never exceed the time limit');
    }

    public function test_time_remaining_is_zero_once_the_limit_has_passed(): void
    {
        $game = $this->makeGame(20, now()->subSeconds(25));

        $this->assertSame(0, $game->timeRemaining());
    }

    public function test_time_remaining_is_zero_when_not_in_the_question_state(): void
    {
        $game = $this->makeGame(20, now()->subSeconds(5), 'reviewing');

        $this->assertSame(0, $game->timeRemaining());
    }
}
