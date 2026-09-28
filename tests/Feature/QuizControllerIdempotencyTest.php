<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizControllerIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function actingHost(): array
    {
        $host = User::create(['name' => 'Host', 'email' => 'idem@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Idempotency Quiz']);

        return [$host, $quiz];
    }

    private function questionPayload(array $overrides = []): array
    {
        return array_merge([
            'question_text' => 'What is 2+2?',
            'time_limit' => 20,
            'answer_delay' => 5,
            'points' => 1000,
            'answers' => ['3', '4', '5', '6'],
            'correct_answers' => [1],
        ], $overrides);
    }

    public function test_resubmitting_add_question_with_the_same_token_creates_only_one_question(): void
    {
        [$host, $quiz] = $this->actingHost();
        $payload = $this->questionPayload(['request_token' => 'tok-abc']);

        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz), $payload)->assertRedirect();
        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz), $payload)->assertRedirect();

        $this->assertSame(1, $quiz->questions()->count());
    }

    public function test_add_question_without_a_token_still_works_and_is_not_deduplicated(): void
    {
        [$host, $quiz] = $this->actingHost();
        $payload = $this->questionPayload();

        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz), $payload)->assertRedirect();
        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz), $payload)->assertRedirect();

        $this->assertSame(2, $quiz->questions()->count(), 'two separate submissions with no token are each legitimate');
    }

    public function test_a_different_token_is_treated_as_a_separate_submission(): void
    {
        [$host, $quiz] = $this->actingHost();

        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz), $this->questionPayload(['request_token' => 'tok-1']))->assertRedirect();
        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz), $this->questionPayload(['request_token' => 'tok-2']))->assertRedirect();

        $this->assertSame(2, $quiz->questions()->count());
    }

    public function test_resubmitting_update_question_with_the_same_token_only_writes_the_answers_once(): void
    {
        [$host, $quiz] = $this->actingHost();
        $question = Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Old text', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);
        $payload = $this->questionPayload(['question_text' => 'New text', 'request_token' => 'tok-upd']);

        $this->actingAs($host)->put(route('quizzes.updateQuestion', [$quiz, $question]), $payload)->assertRedirect();
        $this->actingAs($host)->put(route('quizzes.updateQuestion', [$quiz, $question]), $payload)->assertRedirect();

        $question->refresh();
        $this->assertSame('New text', $question->question_text);
        $this->assertSame(4, $question->answers()->count(), 'answers must not be duplicated by a repeated submit');
    }

    public function test_editor_view_sends_a_fresh_request_token_with_each_form(): void
    {
        [$host, $quiz] = $this->actingHost();
        Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);

        $html = $this->actingAs($host)->get(route('quizzes.edit', $quiz))->getContent();

        $this->assertMatchesRegularExpression('/name="request_token" value="[A-Za-z0-9]{32}"/', $html);
        $this->assertSame(2, substr_count($html, 'name="request_token"'), 'one on the add-question form, one on the card edit form');
    }

    public function test_reorder_sends_a_request_token(): void
    {
        $src = file_get_contents(resource_path('views/quizzes/edit.blade.php'));
        $this->assertStringContainsString('request_token: requestToken', $src);
    }

    public function test_a_token_from_one_quiz_does_not_suppress_the_same_token_on_another_quiz(): void
    {
        [$host, $quiz1] = $this->actingHost();
        $quiz2 = Quiz::create(['user_id' => $host->id, 'title' => 'Second Quiz']);
        $payload = $this->questionPayload(['request_token' => 'shared-token']);

        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz1), $payload)->assertRedirect();
        $this->actingAs($host)->post(route('quizzes.addQuestion', $quiz2), $payload)->assertRedirect();

        $this->assertSame(1, $quiz1->questions()->count());
        $this->assertSame(1, $quiz2->questions()->count());
    }
}
