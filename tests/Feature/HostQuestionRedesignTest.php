<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Game;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\MakesHostGame;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class HostQuestionRedesignTest extends TestCase
{
    use MakesHostGame, MakesPlayerGame, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function html(string $status, int $questions = 2): string
    {
        ['host' => $host, 'game' => $game] = $this->makeHostGame($status, $questions, 2);

        return $this->actingAs($host)->get(route('game.question', $game))->assertOk()->getContent();
    }

    /** Builds a single-question host game with a controlled answer_delay and elapsed time. */
    private function htmlWithDelay(int $answerDelay, int $elapsedSeconds): string
    {
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $host = User::create(['name' => 'Host', 'email' => 'delayhost'.uniqid().'@test.com', 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Delay Quiz']);
        $question = Question::create([
            'quiz_id' => $quiz->id, 'question_text' => 'Delay Q?',
            'time_limit' => 20, 'answer_delay' => $answerDelay, 'points' => 1000, 'order' => 0,
        ]);
        foreach ([['Alpha', true], ['Bravo', false]] as $a => [$text, $correct]) {
            Answer::create(['question_id' => $question->id, 'answer_text' => $text, 'is_correct' => $correct, 'order' => $a]);
        }
        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => (string) random_int(100000, 999999),
            'status' => 'question', 'current_question' => 0,
            'question_started_at' => now()->subSeconds($elapsedSeconds),
        ]);

        return $this->actingAs($host)->get(route('game.question', $game))->assertOk()->getContent();
    }

    private function src(): string
    {
        return file_get_contents(resource_path('views/host/question.blade.php'));
    }

    private function script(): string
    {
        preg_match('/<script>\s*\(function.*?<\/script>/s', $this->src(), $m);

        return $m[0] ?? '';
    }

    public function test_question_state_has_timer_ring_tiles_and_actions(): void
    {
        $html = $this->html('question');
        foreach ([
            'id="timer-ring"', 'id="timer-ring-fill"', 'id="timer-num"',
            'id="reveal-form"', 'id="reveal-btn"', 'id="answered-count"', 'id="emoji-overlay"',
            'data-confirm="Skip this question?"', 'Question number 0?', 'Alpha', 'Bravo', 'Charlie', 'Delta',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertSame(4, substr_count($html, 'ans-tile host-tile'));
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'class="ans-shape"'));
        $this->assertStringNotContainsString('Response breakdown', $html);
        $this->assertStringNotContainsString('id="autoadvance-msg"', $html);
    }

    public function test_reviewing_state_shows_chart_correct_tile_and_next_form(): void
    {
        $html = $this->html('reviewing');
        foreach (['Response breakdown', 'responses', 'id="next-btn"', 'Next →', 'Answers revealed!', 'id="standings"'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertSame(1, substr_count($html, 'host-tile is-locked ans-1 is-correct'));
        $this->assertStringNotContainsString('id="timer-ring"', $html);
        $this->assertStringNotContainsString('id="reveal-form"', $html);
        $this->assertStringNotContainsString('id="autoadvance-msg"', $html);   // not the last question
    }

    public function test_last_question_review_shows_results_button_and_auto_advance(): void
    {
        $html = $this->html('reviewing', 1);
        $this->assertStringContainsString('🏆 Results', $html);
        $this->assertStringContainsString('id="next-form"', $html);
        $this->assertStringContainsString('id="autoadvance-msg"', $html);
        $this->assertStringContainsString('id="autoadvance-fill"', $html);
    }

    public function test_view_has_no_inline_styles_except_the_chart_bar_height(): void
    {
        $src = $this->src();
        $this->assertDoesNotMatchRegularExpression('/<style/i', $src);
        $this->assertDoesNotMatchRegularExpression('/\son(click|submit)\s*=/i', $src);
        preg_match_all('/\sstyle\s*=\s*"([^"]*)"/i', $src, $m);
        foreach ($m[1] as $value) {
            $this->assertMatchesRegularExpression('/^height:\{\{[^}]*\}\}px$/', $value, "unexpected inline style: {$value}");
        }
        $this->assertCount(1, $m[1], 'exactly one dynamic inline style (chart bar height) is allowed');
    }

    public function test_answer_tiles_use_shared_shapes_not_glyphs(): void
    {
        $src = $this->src();
        $this->assertStringContainsString('<x-answer-shape', $src);
        foreach (['▲', '◆', '●', '■'] as $glyph) {
            $this->assertStringNotContainsString($glyph, $src, "old glyph {$glyph} still present");
        }
    }

    public function test_script_avoids_optional_chaining_and_legacy_pieces(): void
    {
        $js = $this->script();
        $this->assertNotSame('', $js, 'inline script not found');
        $this->assertStringNotContainsString('?.', $js);
        $this->assertStringNotContainsString('??', $js);
        $this->assertStringNotContainsString('createControls', $this->src());
        $this->assertStringNotContainsString('host-countdown', $this->src());   // 3-2-1 overlay was hidden under the reading overlay
        $this->assertStringContainsString('spy:', $js);                          // power-up label for Spy
    }

    /** Review focus 1: no Pusher must not break the timer/reveal. */
    public function test_pusher_is_optional_and_the_timer_does_not_depend_on_it(): void
    {
        $js = $this->script();
        $this->assertStringContainsString('QB.loadPusher', $js);
        $this->assertMatchesRegularExpression('/try\s*\{[^}]*new Pusher/s', $js);
        $this->assertLessThan(strpos($js, 'new Pusher'), strpos($js, 'setInterval'), 'timer must be started before Pusher is touched');
    }

    /**
     * The old 5-second reading overlay used a sessionStorage "have we shown this already" hack
     * to survive a mid-question refresh. The new answer_delay gating is driven entirely by the
     * server's elapsed-time calculation (delay_remaining), so it survives a refresh for free and
     * no longer needs sessionStorage at all.
     */
    public function test_answer_delay_gating_survives_refresh_without_session_storage(): void
    {
        $js = $this->script();
        $this->assertStringNotContainsString('sessionStorage', $js);
        $this->assertStringContainsString('delayLeft', $js);
    }

    public function test_reveal_is_submitted_at_most_once(): void
    {
        $js = $this->script();
        $this->assertStringContainsString('function submitReveal()', $js);
        $this->assertStringContainsString('if (revealed || submitting) return;', $js);
        $this->assertSame(0, substr_count($js, "getElementById('reveal-form').submit()"));
    }

    public function test_skip_confirmation_is_a_data_attribute_handled_in_script(): void
    {
        $js = $this->script();
        $this->assertStringContainsString('form[data-confirm]', $js);
        $this->assertStringContainsString('window.confirm', $js);
    }

    public function test_timer_ring_fraction_is_clamped_to_one(): void
    {
        $this->assertStringContainsString('Math.min(1, timeLeft / timeLimit)', $this->script());
    }

    public function test_manual_reveal_and_confirmed_skip_lock_the_reveal_flag(): void
    {
        $js = $this->script();
        // (a) a host-submitted reveal form flips the flag (and does not call submitReveal, which would no-op)
        $this->assertMatchesRegularExpression(
            "/getElementById\('reveal-form'\);\s*if \(revealForm\) revealForm\.addEventListener\('submit', \(\) => \{ revealed = true; \}\);/",
            $js
        );
        // (b) a confirmed skip flips the flag so a pending tick cannot reveal instead
        $this->assertMatchesRegularExpression(
            "/window\.confirm\(f\.dataset\.confirm\)\) e\.preventDefault\(\);\s*else revealed = true;/",
            $js
        );
    }

    /** Final-review Group A: double-submit guard on every host form. */
    public function test_forms_are_locked_against_double_submission(): void
    {
        $js = $this->script();
        $this->assertMatchesRegularExpression('/let\s+submitting\s+=\s+false;/', $js);
        $this->assertStringContainsString('function lockAndSubmit(form)', $js);
        $this->assertStringContainsString('if (submitting) return false;', $js);
        $this->assertStringNotContainsString('form.submit()', $js, 'lockAndSubmit must not submit the form itself');

        // Every host form gates its submit on lockAndSubmit.
        $this->assertMatchesRegularExpression("/revealForm\.addEventListener\('submit', e => \{ if \(!lockAndSubmit\(revealForm\)\) e\.preventDefault\(\); \}\);/", $js);
        $this->assertMatchesRegularExpression("/nextForm\.addEventListener\('submit', e => \{ if \(!lockAndSubmit\(nextForm\)\) e\.preventDefault\(\); \}\);/", $js);
        $this->assertMatchesRegularExpression("/standingsForm\.addEventListener\('submit', e => \{ if \(!lockAndSubmit\(standingsForm\)\) e\.preventDefault\(\); \}\);/", $js);
        // Skip form: lock is only checked once the confirm dialog has been accepted (defaultPrevented stays false).
        $this->assertMatchesRegularExpression("/if \(!e\.defaultPrevented\) \{ if \(!lockAndSubmit\(f\)\) e\.preventDefault\(\); \}/", $js);

        // submitReveal() itself must respect the flag and route through lockAndSubmit rather than a bare submit.
        $this->assertMatchesRegularExpression('/if \(f && lockAndSubmit\(f\)\) f\.submit\(\);/', $js);
    }

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('host/question.blade.php');
    }

    /**
     * Final-review Group E: .host-tile.is-wrong at .55 opacity fails ~4.5:1 contrast against the
     * dark ink text on the projector (worst case: the coral tile only clears ~2.67:1). Bump it to
     * .8 — the lowest value that clears 4.5:1 for every answer color — without touching the
     * unrelated Phase 1/2 .ans-tile.is-wrong / .review-answers .ans-tile.is-wrong rules.
     */
    public function test_host_wrong_tile_opacity_meets_contrast_and_leaves_other_rules_alone(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('.host-tile.is-wrong{opacity:.8}', $css);
        $this->assertStringContainsString('.ans-tile.is-wrong{opacity:.28;filter:grayscale(.5)}', $css);
        $this->assertStringContainsString('.review-answers .ans-tile.is-wrong{opacity:.55}', $css);
    }

    public function test_answer_tiles_are_hidden_and_wait_message_shown_while_the_delay_has_not_elapsed(): void
    {
        $html = $this->htmlWithDelay(answerDelay: 10, elapsedSeconds: 3);

        $this->assertStringContainsString('<div class="hq-answers hidden" id="hq-answers"', $html);
        $this->assertStringContainsString('<p class="hq-answers-wait" id="answers-wait-msg"', $html);
    }

    public function test_answer_tiles_are_visible_and_wait_message_hidden_once_the_delay_has_elapsed(): void
    {
        $html = $this->htmlWithDelay(answerDelay: 0, elapsedSeconds: 0);

        $this->assertStringContainsString('<div class="hq-answers" id="hq-answers"', $html);
        $this->assertStringContainsString('<p class="hq-answers-wait hidden" id="answers-wait-msg"', $html);
    }

    public function test_old_reading_overlay_and_session_storage_key_are_gone(): void
    {
        $src = $this->src();
        $this->assertStringNotContainsString('qb_read_q_', $src);
        $this->assertStringNotContainsString('id="reading-overlay"', $src);
    }

    public function test_question_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            '.host-question', '.timer-ring', '.timer-ring-fill', '.is-urgent', '.revealed-badge', '.hq-card', '.hq-text',
            '.hq-answers', '.host-tile', '.response-chart', '.response-bars', '.bars-4', '.response-bar',
            '.hq-answers-wait', '.timer-ring-delay',
            '.progress-dot', '.host-actions', '.emoji-overlay', '.emoji-float', '.autoadvance-fill', '.autoadvance-msg',
            '@keyframes emoji-float', '@keyframes pulse-num',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
