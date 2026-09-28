<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Game;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpectatorRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpectatingGame(string $status = 'waiting'): Game
    {
        $host = User::create(['name' => 'Host', 'email' => 'spec@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Spec Quiz']);
        $question = Question::create(['quiz_id' => $quiz->id, 'question_text' => 'Q?', 'time_limit' => 20, 'points' => 1000, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'answer_text' => 'B', 'is_correct' => false, 'order' => 1]);
        return Game::create(['quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => '960001', 'status' => $status, 'current_question' => 0, 'spectator_mode' => true]);
    }

    private function watchAs(Game $game): string
    {
        return $this->withSession(['spectator_id_' . $game->pin => 1])->get(route('spectate.watch', $game->pin))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/spectator/watch.blade.php'));
    }

    public function test_page_has_every_state_container_and_hook(): void
    {
        $html = $this->watchAs($this->makeSpectatingGame());
        foreach ([
            'id="spec-waiting"', 'id="spec-question"', 'id="spec-reviewing"', 'id="spec-finished"',
            'id="spec-timer"', 'id="spec-bar"', 'id="spec-progress"', 'id="spec-qtext"',
            'id="spec-answers"', 'id="spec-answered"', 'id="spec-bars"', 'id="spec-lb"',
            'id="spec-review-answers"', 'id="spec-review-lb"', 'id="spec-final-lb"', 'id="spec-emoji-overlay"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
    }

    public function test_no_inline_styles_or_glyph_shapes(): void
    {
        $src = $this->src();
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=|<style/i', $src);
        foreach (['▲', '◆', '●', '■'] as $glyph) {
            $this->assertStringNotContainsString($glyph, $src, "old glyph {$glyph} still present");
        }
        $this->assertStringContainsString('QB.Shapes.svg(', $src);
    }

    /** Review focus 1: js.pusher.com blocked or slow must not break the whole page. */
    public function test_pusher_is_loaded_asynchronously_with_a_polling_fallback(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('QB.loadPusher', $src);
        $this->assertStringContainsString('/js/qb-pusher.js', $src);
        $this->assertStringNotContainsString('js.pusher.com', $src);
        $this->assertMatchesRegularExpression('/try\s*\{[^}]*new Pusher/s', $src);
        $this->assertStringContainsString('pollState()', $src);
        // pollState must be called unconditionally, not only from inside the Pusher branch —
        // find its first call site and confirm it is outside the QB.loadPusher callback.
        $firstCallSite = strpos($src, 'pollState();');
        $loadPusherSite = strpos($src, 'QB.loadPusher(');
        $this->assertNotFalse($firstCallSite);
        $this->assertLessThan($loadPusherSite, $firstCallSite, 'polling must start before/independently of the Pusher load attempt');
    }

    /** Review focus 2: answer/question text must never go through innerHTML. */
    public function test_dynamic_text_never_goes_through_a_template_literal_into_innerhtml(): void
    {
        $src = $this->src();
        $this->assertDoesNotMatchRegularExpression('/innerHTML\s*=\s*`[^`]*\$\{/s', $src, 'no interpolated template literal may be assigned to innerHTML');
        $this->assertStringContainsString('.textContent = ', $src);
    }

    public function test_html_in_an_answer_is_shown_literally_not_executed(): void
    {
        $game = $this->makeSpectatingGame('reviewing');
        $game->quiz->questions->first()->answers()->create(['answer_text' => '<b>bold</b>', 'is_correct' => false, 'order' => 2]);
        // the initial page load doesn't render live question state (that's client-side via polling/Reverb),
        // so this test only proves the server-rendered shell has no injection point; the browser smoke test
        // in Task 6 proves the client-side rendering path end-to-end with a live poll.
        $html = $this->watchAs($game);
        $this->assertStringNotContainsString('<b>bold</b>', $html);
    }

    public function test_spectator_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach (['.spec-answers', 'response-bars', 'response-col', 'response-bar', 'response-label'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
