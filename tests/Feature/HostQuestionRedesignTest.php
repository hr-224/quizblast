<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesHostGame;
use Tests\Concerns\MakesPlayerGame;
use Tests\TestCase;

class HostQuestionRedesignTest extends TestCase
{
    use RefreshDatabase, MakesHostGame, MakesPlayerGame;

    private function html(string $status, int $questions = 2): string
    {
        ['host' => $host, 'game' => $game] = $this->makeHostGame($status, $questions, 2);
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
            'id="timer-ring"', 'id="timer-ring-fill"', 'id="timer-num"', 'id="reading-overlay"', 'id="reading-ring"',
            'id="reading-num"', 'id="reveal-form"', 'id="reveal-btn"', 'id="answered-count"', 'id="emoji-overlay"',
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
        $this->assertStringContainsString("spy:", $js);                          // power-up label for Spy
    }

    /** Review focus 1: no Pusher must not break the timer/reveal. */
    public function test_pusher_is_optional_and_the_timer_does_not_depend_on_it(): void
    {
        $js = $this->script();
        $this->assertStringContainsString('window.Pusher', $js);
        $this->assertMatchesRegularExpression('/try\s*\{[^}]*new Pusher/s', $js);
        $this->assertLessThan(strpos($js, 'new Pusher'), strpos($js, 'setInterval'), 'timer must be started before Pusher is touched');
    }

    /** Review focus 3: refresh mid-question works without sessionStorage. */
    public function test_reading_overlay_survives_blocked_session_storage(): void
    {
        $js = $this->script();
        $this->assertMatchesRegularExpression('/try\s*\{[^}]*sessionStorage/s', $js);
        $this->assertStringContainsString("classList.remove('hidden')", $js);
    }

    public function test_reveal_is_submitted_at_most_once(): void
    {
        $js = $this->script();
        $this->assertStringContainsString('function submitReveal()', $js);
        $this->assertStringContainsString('if (revealed) return;', $js);
        $this->assertSame(0, substr_count($js, "getElementById('reveal-form').submit()"));
    }

    public function test_skip_confirmation_is_a_data_attribute_handled_in_script(): void
    {
        $js = $this->script();
        $this->assertStringContainsString("form[data-confirm]", $js);
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

    public function test_every_id_the_script_reads_exists_in_the_markup(): void
    {
        $this->assertScriptIdsExist('host/question.blade.php');
    }

    public function test_question_css_exists(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        foreach ([
            '.host-question', '.timer-ring', '.timer-ring-fill', '.is-urgent', '.revealed-badge', '.hq-card', '.hq-text',
            '.hq-answers', '.host-tile', '.response-chart', '.response-bars', '.bars-4', '.response-bar', '.host-reading',
            '.progress-dot', '.host-actions', '.emoji-overlay', '.emoji-float', '.autoadvance-fill', '.autoadvance-msg',
            '@keyframes emoji-float', '@keyframes pulse-num',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }
    }
}
