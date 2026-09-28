<?php

namespace Tests\Feature;

use App\Events\GameStateChanged;
use App\Models\Answer;
use App\Models\Game;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnswerDelayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // Freezes "now" for the duration of the test so DB setup work (which takes a real,
    // variable amount of wall-clock time) can never shift the elapsed-seconds math by
    // crossing a whole-second boundary between question_started_at and the assertion.
    private function makeGame(int $answerDelay, int $timeLimit, int $elapsedSeconds, string $status = 'question', array $questionAttrs = []): array
    {
        // Round to a whole second: question_started_at is stored/read with only whole-second
        // precision (the datetime cast/column drops microseconds), so freezing "now" at a
        // sub-second instant would leave a fractional-second bias between it and the
        // truncated question_started_at, making the elapsed-seconds math off by one.
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $host = User::create(['name' => 'Host', 'email' => 'delay'.uniqid().'@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Delay Quiz']);
        $question = Question::create(array_merge([
            'quiz_id' => $quiz->id,
            'question_text' => 'Q?',
            'time_limit' => $timeLimit,
            'answer_delay' => $answerDelay,
            'points' => 1000,
            'order' => 0,
        ], $questionAttrs));
        Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => false, 'order' => 1]);

        $game = Game::create([
            'quiz_id' => $quiz->id,
            'user_id' => $host->id,
            'pin' => (string) random_int(100000, 999999),
            'status' => $status,
            'current_question' => 0,
            'question_started_at' => $status === 'question' || $status === 'reviewing' ? now()->subSeconds($elapsedSeconds) : null,
        ]);

        return compact('host', 'quiz', 'question', 'game');
    }

    // ── Game::timeRemaining() / Game::delayRemaining() ─────────────────────

    public function test_while_still_in_the_delay_time_remaining_is_the_full_limit_and_delay_remaining_counts_down(): void
    {
        ['game' => $game] = $this->makeGame(answerDelay: 5, timeLimit: 20, elapsedSeconds: 2);

        $this->assertSame(20, $game->timeRemaining());
        $this->assertSame(3, $game->delayRemaining());
    }

    public function test_once_the_delay_has_elapsed_time_remaining_counts_down_from_the_moment_the_delay_ended(): void
    {
        // delay = 5, elapsed = 8 → 3s into the answer window → 20 - 3 = 17 remaining
        ['game' => $game] = $this->makeGame(answerDelay: 5, timeLimit: 20, elapsedSeconds: 8);

        $this->assertSame(17, $game->timeRemaining());
        $this->assertSame(0, $game->delayRemaining());
    }

    public function test_both_return_zero_when_status_is_not_question(): void
    {
        ['game' => $game] = $this->makeGame(answerDelay: 5, timeLimit: 20, elapsedSeconds: 2, status: 'reviewing');

        $this->assertSame(0, $game->timeRemaining());
        $this->assertSame(0, $game->delayRemaining());
    }

    public function test_both_return_zero_when_question_started_at_is_null(): void
    {
        $host = User::create(['name' => 'Host', 'email' => 'delaynull@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Delay Quiz']);
        Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'answer_delay' => 5, 'points' => 1000, 'order' => 0]);
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '444001',
            'status' => 'question', 'current_question' => 0, 'question_started_at' => null,
        ]);

        $this->assertSame(0, $game->timeRemaining());
        $this->assertSame(0, $game->delayRemaining());
    }

    public function test_a_zero_answer_delay_behaves_like_the_old_undelayed_countdown_from_the_first_second(): void
    {
        ['game' => $game] = $this->makeGame(answerDelay: 0, timeLimit: 20, elapsedSeconds: 3);

        $this->assertSame(17, $game->timeRemaining());
        $this->assertSame(0, $game->delayRemaining());
    }

    public function test_delay_remaining_is_not_shortened_by_sub_second_elapsed_time(): void
    {
        // question_started_at is stored with whole-second precision (the datetime cast/
        // column drops microseconds), but a real "now" mid-request keeps sub-second
        // precision. Without ceil() in Game::delayRemaining(), that fractional elapsed time
        // truncates toward zero and can under-report the delay by up to a full second.
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $host = User::create(['name' => 'Host', 'email' => 'subsecond'.uniqid().'@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Delay Quiz']);
        Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'answer_delay' => 5, 'points' => 1000, 'order' => 0]);
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => (string) random_int(100000, 999999),
            'status' => 'question', 'current_question' => 0, 'question_started_at' => now(),
        ])->fresh();

        // Advance the frozen clock 600ms within the same whole second the question started.
        Carbon::setTestNow(Carbon::now()->addMilliseconds(600));

        $this->assertSame(5, $game->delayRemaining());
    }

    // ── GameController::state() ─────────────────────────────────────────

    public function test_state_endpoint_mid_delay_reports_full_time_and_positive_delay_remaining(): void
    {
        ['game' => $game] = $this->makeGame(answerDelay: 10, timeLimit: 20, elapsedSeconds: 3);

        $response = $this->getJson("/api/game/{$game->pin}/state");

        $response->assertOk();
        $response->assertJsonPath('delay_remaining', 7);
        $response->assertJsonPath('time_remaining', 20);
        $response->assertJsonPath('question.answer_delay', 10);
    }

    public function test_state_endpoint_after_delay_elapsed_reports_zero_delay_and_counting_down_time(): void
    {
        ['game' => $game] = $this->makeGame(answerDelay: 5, timeLimit: 20, elapsedSeconds: 12);

        $response = $this->getJson("/api/game/{$game->pin}/state");

        $response->assertOk();
        $response->assertJsonPath('delay_remaining', 0);
        $data = $response->json();
        $this->assertLessThan(20, $data['time_remaining']);
    }

    // ── GameStateChanged::broadcastWith() ───────────────────────────────

    public function test_broadcast_payload_includes_delay_remaining_and_media_fields(): void
    {
        ['game' => $game] = $this->makeGame(
            answerDelay: 10,
            timeLimit: 20,
            elapsedSeconds: 2,
            questionAttrs: ['image_url' => 'https://example.com/pic.png', 'video_url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ']
        );

        $payload = (new GameStateChanged($game))->broadcastWith();

        $this->assertArrayHasKey('delay_remaining', $payload);
        $this->assertSame(8, $payload['delay_remaining']);
        $this->assertSame('https://example.com/pic.png', $payload['question']['image_url']);
        $this->assertSame('https://youtube.com/watch?v=dQw4w9WgXcQ', $payload['question']['video_url']);
        $this->assertSame('dQw4w9WgXcQ', $payload['question']['youtube_id']);
        $this->assertSame(10, $payload['question']['answer_delay']);
    }
}
